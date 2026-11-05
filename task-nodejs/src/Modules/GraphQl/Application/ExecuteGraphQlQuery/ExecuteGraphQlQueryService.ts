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

import { GraphQlEnum } from "#app/Modules/GraphQl/Domain/Enums/GraphQlEnum.ts";
import { ExecuteGraphQlRequestDto } from "#app/Modules/GraphQl/Application/ExecuteGraphQlQuery/ExecuteGraphQlRequestDto.ts";
import { ExecuteGraphQlResultDto } from "#app/Modules/GraphQl/Application/ExecuteGraphQlQuery/ExecuteGraphQlResultDto.ts";
import { GraphQlSchemaException } from "#app/Modules/GraphQl/Infrastructure/Exceptions/GraphQlSchemaException.ts";
import { GraphQlSchemaProvidersRegistry } from "#app/Modules/GraphQl/Infrastructure/Providers/GraphQlSchemaProvidersRegistry.ts";
import type { GraphQlResolversByType } from "#app/Modules/GraphQl/Infrastructure/Providers/InterfaceGraphQlSchemaProvider.ts";
import { getQueryDepthRule } from "#app/Modules/GraphQl/Infrastructure/Validators/QueryDepthRule.ts";

/**
 * El schema ya montado y la tabla de resolvers con la que se ejecuta.
 */
type GraphQlExecutableSchema = {
    schema: GraphQLSchema;
    fieldResolver: GraphQLFieldResolver<unknown, unknown>;
};

// el schema no depende de la peticion (los resolvers crean su XxxGraphQlController al llamarse): uno por proceso
let executeGraphQlQueryService: ExecuteGraphQlQueryService | null = null;

/**
 * Monta el schema con lo que registran los modulos y ejecuta la peticion con graphql-js: el unico sitio (junto a
 * GraphQlException y QueryDepthRule) que conoce la libreria. Servicio: no captura ni loguea.
 */
export class ExecuteGraphQlQueryService {

    readonly #graphqlSchemaRegistry: GraphQlSchemaProvidersRegistry;
    #executableSchema: GraphQlExecutableSchema | null = null;

    private constructor(graphqlSchemaRegistry: GraphQlSchemaProvidersRegistry) {
        this.#graphqlSchemaRegistry = graphqlSchemaRegistry;
    }

    static getInstance(): ExecuteGraphQlQueryService {
        executeGraphQlQueryService ??= new ExecuteGraphQlQueryService(GraphQlSchemaProvidersRegistry.getInstance());
        return executeGraphQlQueryService;
    }

    /**
     * El GraphQL::executeQuery() de webonyx: parse + validacion (todas las reglas y la profundidad maxima) + ejecucion.
     * Los errores de GraphQL (sintaxis, validacion, dominio de un resolver) van en el resultado, no se lanzan.
     *
     * @throws GraphQlSchemaException si el registro esta mal montado (campo repetido o sin resolver)
     */
    invoke(executeGraphQlRequestDto: ExecuteGraphQlRequestDto): ExecuteGraphQlResultDto {
        const { schema, fieldResolver } = this.#getExecutableSchema();

        let document: DocumentNode;
        try {
            document = parse(executeGraphQlRequestDto.getQuery());
        }
        catch (e) {
            if (e instanceof GraphQLError) {
                return this.#getExecuteGraphQlResultDto([e]);
            }
            throw e;
        }

        const validationErrors = validate(
            schema,
            document,
            [...specifiedRules, getQueryDepthRule(GraphQlEnum.MAX_QUERY_DEPTH)]
        );
        if (validationErrors.length) {
            return this.#getExecuteGraphQlResultDto(validationErrors);
        }

        const executionResult = executeSync({
            schema,
            document,
            variableValues: executeGraphQlRequestDto.getVariables(),
            operationName: executeGraphQlRequestDto.getOperationName() ?? undefined,
            fieldResolver,
        });
        return this.#getExecuteGraphQlResultDto(executionResult.errors ?? [], executionResult.data);
    }

    /**
     * El ExecutionResult::toArray() de webonyx: cada error serializado y data tal cual.
     */
    #getExecuteGraphQlResultDto(errors: readonly GraphQLError[], data: unknown = null): ExecuteGraphQlResultDto {
        return ExecuteGraphQlResultDto.fromPrimitives({
            [GraphQlEnum.KEY_ERRORS]: errors.map((error) => error.toJSON()),
            [GraphQlEnum.KEY_DATA]: data,
        });
    }

    /**
     * @throws GraphQlSchemaException
     */
    #getExecutableSchema(): GraphQlExecutableSchema {
        if (this.#executableSchema) return this.#executableSchema;

        const resolversByType = this.#graphqlSchemaRegistry.getAllMergedResolversByType();
        //todos los esquemas .graphql de los modulos, en el orden del registro, con la cabecera comun schema.graphql
        const schema = buildSchema(this.#graphqlSchemaRegistry.getGraphQlSchemaAsString());
        this.#failIfRootFieldWithoutResolver(schema, resolversByType);

        this.#executableSchema = {
            schema,
            // graphql-js no tiene el typeConfigDecorator de webonyx: los campos sin resolver propio salen del objeto
            // del ResultDto (resolver por defecto)
            fieldResolver: (objectValue, gqlPayload, contextValue, resolveInfo) => {
                const resolver = resolversByType[resolveInfo.parentType.name]?.[resolveInfo.fieldName];
                return resolver
                    ? resolver(gqlPayload, objectValue as Primitives)
                    : defaultFieldResolver(objectValue, gqlPayload, contextValue, resolveInfo);
            },
        };
        return this.#executableSchema;
    }

    /**
     * Un campo de Query o Mutation sin resolver devolveria null sin avisar: es un fallo del registro.
     *
     * @throws GraphQlSchemaException
     */
    #failIfRootFieldWithoutResolver(schema: GraphQLSchema, resolversByType: GraphQlResolversByType): void {
        for (const rootTypeName of [GraphQlEnum.TYPE_QUERY, GraphQlEnum.TYPE_MUTATION]) {
            const rootType = assertObjectType(schema.getType(rootTypeName));
            const fieldsWithoutResolver = Object.keys(rootType.getFields())
                .filter((fieldName) => !(fieldName in (resolversByType[rootTypeName] ?? {})));
            if (fieldsWithoutResolver.length) {
                GraphQlSchemaException.fieldsWithoutResolver(rootTypeName, fieldsWithoutResolver);
            }
        }
    }
}
