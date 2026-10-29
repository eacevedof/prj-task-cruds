import { readFileSync } from "node:fs";

import { graphqlSchemaProviders } from "#app/Core/config/graphqlSchema.ts";

import { GraphqlSchemaException } from "#app/Modules/Graphql/Infrastructure/Exceptions/GraphqlSchemaException.ts";
import type {
    GraphqlResolversByType,
    InterfaceGraphqlSchemaProvider,
    InterfaceGraphqlSchemaProviderClass,
} from "#app/Modules/Graphql/Infrastructure/Schema/InterfaceGraphqlSchemaProvider.ts";

/**
 * Une lo que registran los modulos en src/Core/config/graphqlSchema.ts: la cabecera comun (schema.graphql) mas el
 * SDL de cada proveedor, y la tabla tipo.campo => resolver. Es a /graphql lo que routes.ts al ApiRouter.
 */
export class GraphqlSchemaRegistry {

    readonly #schemaProviders: InterfaceGraphqlSchemaProvider[];

    private constructor(schemaProviderClasses: readonly InterfaceGraphqlSchemaProviderClass[]) {
        this.#schemaProviders = schemaProviderClasses.map((providerClass) => providerClass.getInstance());
    }

    static getInstance(): GraphqlSchemaRegistry {
        return new GraphqlSchemaRegistry(graphqlSchemaProviders);
    }

    /**
     * En el orden del registro: el modulo que define un tipo va antes que los que lo extienden.
     */
    getSdl(): string {
        return [
            readFileSync(new URL("schema.graphql", import.meta.url), "utf8"),
            ...this.#schemaProviders.map((schemaProvider) => schemaProvider.getSdl()),
        ].join("\n");
    }

    /**
     * @throws GraphqlSchemaException si dos modulos resuelven el mismo campo (uno pisaria al otro sin avisar)
     */
    getResolversByType(): GraphqlResolversByType {
        const resolversByType: GraphqlResolversByType = {};
        for (const schemaProvider of this.#schemaProviders) {
            for (const [typeName, resolvers] of Object.entries(schemaProvider.getResolversByType())) {
                const repeatedFields = Object.keys(resolvers).filter((fieldName) => fieldName in (resolversByType[typeName] ?? {}));
                if (repeatedFields.length) {
                    GraphqlSchemaException.fieldsAlreadyHaveResolver(typeName, repeatedFields, schemaProvider.constructor.name);
                }
                resolversByType[typeName] = { ...resolversByType[typeName], ...resolvers };
            }
        }
        return resolversByType;
    }
}
