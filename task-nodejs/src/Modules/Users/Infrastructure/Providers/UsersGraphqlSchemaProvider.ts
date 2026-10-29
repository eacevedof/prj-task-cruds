import { readFileSync } from "node:fs";

import { GraphqlEnum } from "#app/Modules/Graphql/Domain/Enums/GraphqlEnum.ts";
import type {
    GraphqlResolversByType,
    InterfaceGraphqlSchemaProvider,
} from "#app/Modules/Graphql/Infrastructure/Schema/InterfaceGraphqlSchemaProvider.ts";

import { CreateUserGraphqlController } from "#app/Modules/Users/Infrastructure/Controllers/CreateUserGraphqlController.ts";
import { DeleteUserGraphqlController } from "#app/Modules/Users/Infrastructure/Controllers/DeleteUserGraphqlController.ts";
import { GetUserGraphqlController } from "#app/Modules/Users/Infrastructure/Controllers/GetUserGraphqlController.ts";
import { ListUsersGraphqlController } from "#app/Modules/Users/Infrastructure/Controllers/ListUsersGraphqlController.ts";
import { LogInUserGraphqlController } from "#app/Modules/Users/Infrastructure/Controllers/LogInUserGraphqlController.ts";
import { RegenerateUserApiTokenGraphqlController } from "#app/Modules/Users/Infrastructure/Controllers/RegenerateUserApiTokenGraphqlController.ts";
import { UpdateUserGraphqlController } from "#app/Modules/Users/Infrastructure/Controllers/UpdateUserGraphqlController.ts";

/**
 * Parte de users en /graphql: users.graphql (define Query, Mutation y User) y sus resolvers. El resolver se crea
 * solo si la consulta pide su campo.
 */
export class UsersGraphqlSchemaProvider implements InterfaceGraphqlSchemaProvider {

    static getInstance(): UsersGraphqlSchemaProvider {
        return new UsersGraphqlSchemaProvider();
    }

    getSdl(): string {
        return readFileSync(new URL("users.graphql", import.meta.url), "utf8");
    }

    getResolversByType(): GraphqlResolversByType {
        return {
            [GraphqlEnum.TYPE_QUERY]: {
                users: () => ListUsersGraphqlController.getInstance().invoke(),
                user: (args) => GetUserGraphqlController.getInstance().invoke(args),
            },
            [GraphqlEnum.TYPE_MUTATION]: {
                createUser: (args) => CreateUserGraphqlController.getInstance().invoke(args),
                logIn: (args) => LogInUserGraphqlController.getInstance().invoke(args),
                updateUser: (args) => UpdateUserGraphqlController.getInstance().invoke(args),
                deleteUser: (args) => DeleteUserGraphqlController.getInstance().invoke(args),
                regenerateApiToken: (args) => RegenerateUserApiTokenGraphqlController.getInstance().invoke(args),
            },
        };
    }
}
