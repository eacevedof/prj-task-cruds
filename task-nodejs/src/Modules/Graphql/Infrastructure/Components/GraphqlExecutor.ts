import {
    assertObjectType,
    buildSchema,
    defaultFieldResolver,
    executeSync,
    GraphQLError,
    parse,
    specifiedRules,
    validate,
    type DocumentNode,
    type GraphQLFieldResolver,
    type GraphQLSchema,
} from "graphql";

import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

import { GraphqlEnum } from "#app/Modules/Graphql/Domain/Enums/GraphqlEnum.ts";
import { GraphqlRequestDto } from "#app/Modules/Graphql/Infrastructure/Dtos/GraphqlRequestDto.ts";
import { GraphqlResultDto } from "#app/Modules/Graphql/Infrastructure/Dtos/GraphqlResultDto.ts";
import { GraphqlSchemaException } from "#app/Modules/Graphql/Infrastructure/Exceptions/GraphqlSchemaException.ts";
import { GraphqlSchemaRegistry } from "#app/Modules/Graphql/Infrastructure/Schema/GraphqlSchemaRegistry.ts";
import type { GraphqlResolversByType } from "#app/Modules/Graphql/Infrastructure/Schema/InterfaceGraphqlSchemaProvider.ts";
import { getQueryDepthRule } from "#app/Modules/Graphql/Infrastructure/Validators/QueryDepthRule.ts";

/**
 * El schema ya montado y la tabla de resolvers con la que se ejecuta.
 */
type GraphqlExecutableSchema = {
    schema: GraphQLSchema;
    fieldResolver: GraphQLFieldResolver<unknown, unknown>;
};

// el schema no depende de la peticion (los resolvers crean su XxxGraphqlController al llamarse): uno por proceso
let graphqlExecutor: GraphqlExecutor | null = null;

/**
 * Monta el schema con lo que registran los modulos y ejecuta la peticion con graphql-js: el unico sitio (junto a
 * AbstractGraphqlController y QueryDepthRule) que conoce la libreria. Componente: no captura ni loguea.
 */
export class GraphqlExecutor {

    readonly #graphqlSchemaRegistry: GraphqlSchemaRegistry;
    #executableSchema: GraphqlExecutableSchema | null = null;

    private constructor(graphqlSchemaRegistry: GraphqlSchemaRegistry) {
        this.#graphqlSchemaRegistry = graphqlSchemaRegistry;
    }

    static getInstance(): GraphqlExecutor {
        graphqlExecutor ??= new GraphqlExecutor(GraphqlSchemaRegistry.getInstance());
        return graphqlExecutor;
    }

    /**
     * El GraphQL::executeQuery() de webonyx: parse + validacion (todas las reglas y la profundidad maxima) + ejecucion.
     * Los errores de GraphQL (sintaxis, validacion, dominio de un resolver) van en el resultado, no se lanzan.
     *
     * @throws GraphqlSchemaException si el registro esta mal montado (campo repetido o sin resolver)
     */
    execute(graphqlRequestDto: GraphqlRequestDto): GraphqlResultDto {
        const { schema, fieldResolver } = this.#getExecutableSchema();

        let document: DocumentNode;
        try {
            document = parse(graphqlRequestDto.getQuery());
        }
        catch (e) {
            if (e instanceof GraphQLError) {
                return this.#getGraphqlResultDto([e]);
            }
            throw e;
        }

        const validationErrors = validate(
            schema,
            document,
            [...specifiedRules, getQueryDepthRule(GraphqlEnum.MAX_QUERY_DEPTH)]
        );
        if (validationErrors.length) {
            return this.#getGraphqlResultDto(validationErrors);
        }

        const executionResult = executeSync({
            schema,
            document,
            variableValues: graphqlRequestDto.getVariables(),
            operationName: graphqlRequestDto.getOperationName() ?? undefined,
            fieldResolver,
        });
        return this.#getGraphqlResultDto(executionResult.errors ?? [], executionResult.data);
    }

    /**
     * El ExecutionResult::toArray() de webonyx: cada error serializado y data tal cual.
     */
    #getGraphqlResultDto(errors: readonly GraphQLError[], data: unknown = null): GraphqlResultDto {
        return GraphqlResultDto.fromPrimitives({
            [GraphqlEnum.KEY_ERRORS]: errors.map((error) => error.toJSON()),
            [GraphqlEnum.KEY_DATA]: data,
        });
    }

    /**
     * @throws GraphqlSchemaException
     */
    #getExecutableSchema(): GraphqlExecutableSchema {
        if (this.#executableSchema) return this.#executableSchema;

        const resolversByType = this.#graphqlSchemaRegistry.getResolversByType();
        const schema = buildSchema(this.#graphqlSchemaRegistry.getSdl());
        this.#failIfRootFieldWithoutResolver(schema, resolversByType);

        this.#executableSchema = {
            schema,
            // graphql-js no tiene el typeConfigDecorator de webonyx: los campos sin resolver propio salen del objeto
            // del ResultDto (resolver por defecto)
            fieldResolver: (objectValue, args, contextValue, resolveInfo) => {
                const resolver = resolversByType[resolveInfo.parentType.name]?.[resolveInfo.fieldName];
                return resolver
                    ? resolver(args, objectValue as Primitives)
                    : defaultFieldResolver(objectValue, args, contextValue, resolveInfo);
            },
        };
        return this.#executableSchema;
    }

    /**
     * Un campo de Query o Mutation sin resolver devolveria null sin avisar: es un fallo del registro.
     *
     * @throws GraphqlSchemaException
     */
    #failIfRootFieldWithoutResolver(schema: GraphQLSchema, resolversByType: GraphqlResolversByType): void {
        for (const rootTypeName of [GraphqlEnum.TYPE_QUERY, GraphqlEnum.TYPE_MUTATION]) {
            const rootType = assertObjectType(schema.getType(rootTypeName));
            const fieldsWithoutResolver = Object.keys(rootType.getFields())
                .filter((fieldName) => !(fieldName in (resolversByType[rootTypeName] ?? {})));
            if (fieldsWithoutResolver.length) {
                GraphqlSchemaException.fieldsWithoutResolver(rootTypeName, fieldsWithoutResolver);
            }
        }
    }
}
