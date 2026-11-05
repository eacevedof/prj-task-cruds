import { readFileSync } from "node:fs";

import { GraphQlEnum } from "#app/Modules/GraphQl/Domain/Enums/GraphQlEnum.ts";
import type {
    GraphQlResolversByType,
    InterfaceGraphQlSchemaProvider,
} from "#app/Modules/GraphQl/Infrastructure/Providers/InterfaceGraphQlSchemaProvider.ts";

import { CreateUserGraphQlController } from "#app/Modules/Users/Infrastructure/Controllers/CreateUserGraphQlController.ts";
import { DeleteUserGraphQlController } from "#app/Modules/Users/Infrastructure/Controllers/DeleteUserGraphQlController.ts";
import { GetUserGraphQlController } from "#app/Modules/Users/Infrastructure/Controllers/GetUserGraphQlController.ts";
import { ListUsersGraphQlController } from "#app/Modules/Users/Infrastructure/Controllers/ListUsersGraphQlController.ts";
import { LogInUserGraphQlController } from "#app/Modules/Users/Infrastructure/Controllers/LogInUserGraphQlController.ts";
import { RegenerateUserApiTokenGraphQlController } from "#app/Modules/Users/Infrastructure/Controllers/RegenerateUserApiTokenGraphQlController.ts";
import { UpdateUserGraphQlController } from "#app/Modules/Users/Infrastructure/Controllers/UpdateUserGraphQlController.ts";

/**
 * Parte de users en /graphql: users.graphql (define Query, Mutation y User) y sus resolvers. El resolver se crea
 * solo si la consulta pide su campo.
 */
export class UsersGraphQlSchemaProvider implements InterfaceGraphQlSchemaProvider {

    // tipo que define users.graphql y extienden otros modulos (Tasks le anade tasks)
    static readonly TYPE_USER = "User";

    static getInstance(): UsersGraphQlSchemaProvider {
        return new UsersGraphQlSchemaProvider();
    }

    getGraphQlFileContent(): string {
        return readFileSync(new URL("users.graphql", import.meta.url), "utf8");
    }

    getControllerResolvers(): GraphQlResolversByType {
        return {
            [GraphQlEnum.TYPE_QUERY]: this.#getQueryControllers(),
            [GraphQlEnum.TYPE_MUTATION]: this.#getMutationControllers(),
        };
    }

    #getQueryControllers(): GraphQlResolversByType[string] {
        return {
            users: () => ListUsersGraphQlController.getInstance().invoke(),
            user: (gqlPayload) => GetUserGraphQlController.getInstance().invoke(gqlPayload),
        };
    }

    #getMutationControllers(): GraphQlResolversByType[string] {
        return {
            createUser: (gqlPayload) => CreateUserGraphQlController.getInstance().invoke(gqlPayload),
            logIn: (gqlPayload) => LogInUserGraphQlController.getInstance().invoke(gqlPayload),
            updateUser: (gqlPayload) => UpdateUserGraphQlController.getInstance().invoke(gqlPayload),
            deleteUser: (gqlPayload) => DeleteUserGraphQlController.getInstance().invoke(gqlPayload),
            regenerateApiToken: (gqlPayload) => RegenerateUserApiTokenGraphQlController.getInstance().invoke(gqlPayload),
        };
    }
}
