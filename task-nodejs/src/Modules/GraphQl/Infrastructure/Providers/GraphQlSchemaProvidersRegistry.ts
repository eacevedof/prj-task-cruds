import { readFileSync } from "node:fs";

import { GraphQlSchemaException } from "#app/Modules/GraphQl/Infrastructure/Exceptions/GraphQlSchemaException.ts";
import type {
    GraphQlResolversByType,
    InterfaceGraphQlSchemaProvider,
    InterfaceGraphQlSchemaProviderClass,
} from "#app/Modules/GraphQl/Infrastructure/Providers/InterfaceGraphQlSchemaProvider.ts";

import { TasksGraphQlSchemaProvider } from "#app/Modules/Tasks/Infrastructure/Providers/TasksGraphQlSchemaProvider.ts";
import { UsersGraphQlSchemaProvider } from "#app/Modules/Users/Infrastructure/Providers/UsersGraphQlSchemaProvider.ts";

/**
 * Une lo que registran los modulos en getAllModulesProviders(): la cabecera comun (schema.graphql) mas el
 * SDL de cada proveedor, y la tabla tipo.campo => resolver. Es a /graphql lo que routes.ts al ApiRouter.
 */
export class GraphQlSchemaProvidersRegistry {

    readonly #schemaProvidersInstances: InterfaceGraphQlSchemaProvider[];

    private constructor() {
        this.#schemaProvidersInstances = this.#getAllModulesProviders().map(
            (providerClass) => providerClass.getInstance()
        );
    }

    static getInstance(): GraphQlSchemaProvidersRegistry {
        return new GraphQlSchemaProvidersRegistry();
    }

    /**
     * El orden importa: primero los que definen un tipo, despues los que lo extienden.
     * Cada modulo tiene su propio provider
     */
    #getAllModulesProviders(): InterfaceGraphQlSchemaProviderClass[] {
        return [
            UsersGraphQlSchemaProvider,
            TasksGraphQlSchemaProvider,
        ];
    }

    /**
     * En el orden del registro: el modulo que define un tipo va antes que los que lo extienden.
     */
    getGraphQlSchemaAsString(): string {
        const allSchemas = this.#schemaProvidersInstances.map(
            (schemaProviderInstance) => schemaProviderInstance.getGraphQlFileContent()
        );
        return [
            readFileSync(new URL("schema.graphql", import.meta.url), "utf8"),
            ...allSchemas,
        ].join("\n");
    }

    /**
     * Une los getControllerResolvers() de cada provider en una sola tabla tipo => campo => resolver. Los tipos y
     * campos salen en el orden del registro: primero los de Users y detras los que anade Tasks. Cada resolver es
     * una funcion (gqlPayload, parent) que crea su controller solo si la consulta pide ese campo.
     *
     * Con el registro actual (Users, Tasks) devuelve:
     * {
     *     Query: {
     *         users: () => ListUsersGraphQlController.getInstance().invoke(),             // Users
     *         user: (gqlPayload) => GetUserGraphQlController...,                          // Users
     *         tasks: (gqlPayload) => SearchTasksGraphQlController...,                     // Tasks
     *         task: (gqlPayload) => GetTaskGraphQlController...,                          // Tasks
     *     },
     *     Mutation: {
     *         createUser: ..., logIn: ..., updateUser: ..., deleteUser: ...,
     *         regenerateApiToken: ...,                                                    // Users
     *         createTask: ..., updateTask: ..., deleteTask: ...,                          // Tasks
     *     },
     *     User: {
     *         tasks: (gqlPayload, user) => SearchTasksByUserGraphQlController...,         // Tasks
     *     },
     * }
     * "Query.tasks" y "User.tasks" no chocan: la clave de choque es tipo + campo, no solo el campo.
     *
     * @throws GraphQlSchemaException si dos modulos resuelven el mismo campo (uno pisaria al otro sin avisar)
     */
    getAllMergedResolversByType(): GraphQlResolversByType {
        const resolversByType: GraphQlResolversByType = {};
        for (const schemaProviderInstance of this.#schemaProvidersInstances) {
            for (const [typeName, controllerResolvers] of Object.entries(schemaProviderInstance.getControllerResolvers())) {
                const repeatedFields = Object.keys(controllerResolvers)
                    .filter((fieldName) => fieldName in (resolversByType[typeName] ?? {}));
                if (repeatedFields.length) {
                    GraphQlSchemaException.fieldsAlreadyHaveResolver(
                        typeName,
                        repeatedFields,
                        schemaProviderInstance.constructor.name
                    );
                }

                resolversByType[typeName] = {
                    ...resolversByType[typeName],
                    ...controllerResolvers,
                };
            }
        }
        return resolversByType;
    }
}
