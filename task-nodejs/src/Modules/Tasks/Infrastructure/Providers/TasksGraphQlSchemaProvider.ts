import { readFileSync } from "node:fs";

import { getTrimmedString } from "#app/Modules/Shared/Application/Primitives.ts";

import { GraphQlEnum } from "#app/Modules/GraphQl/Domain/Enums/GraphQlEnum.ts";
import type {
    GraphQlResolversByType,
    InterfaceGraphQlSchemaProvider,
} from "#app/Modules/GraphQl/Infrastructure/Providers/InterfaceGraphQlSchemaProvider.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { UsersGraphQlSchemaProvider } from "#app/Modules/Users/Infrastructure/Providers/UsersGraphQlSchemaProvider.ts";

import { CreateTaskGraphQlController } from "#app/Modules/Tasks/Infrastructure/Controllers/CreateTaskGraphQlController.ts";
import { DeleteTaskGraphQlController } from "#app/Modules/Tasks/Infrastructure/Controllers/DeleteTaskGraphQlController.ts";
import { GetTaskGraphQlController } from "#app/Modules/Tasks/Infrastructure/Controllers/GetTaskGraphQlController.ts";
import { SearchTasksByUserGraphQlController } from "#app/Modules/Tasks/Infrastructure/Controllers/SearchTasksByUserGraphQlController.ts";
import { SearchTasksGraphQlController } from "#app/Modules/Tasks/Infrastructure/Controllers/SearchTasksGraphQlController.ts";
import { UpdateTaskGraphQlController } from "#app/Modules/Tasks/Infrastructure/Controllers/UpdateTaskGraphQlController.ts";

/**
 * Parte de tasks en /graphql: tasks.graphql (extiende Query, Mutation y User de users) y sus resolvers, incluido
 * User.tasks, que cuelga de User pero es de tasks. Va despues de UsersGraphQlSchemaProvider en el registro.
 */
export class TasksGraphQlSchemaProvider implements InterfaceGraphQlSchemaProvider {

    static getInstance(): TasksGraphQlSchemaProvider {
        return new TasksGraphQlSchemaProvider();
    }

    getGraphQlFileContent(): string {
        return readFileSync(new URL("tasks.graphql", import.meta.url), "utf8");
    }

    getControllerResolvers(): GraphQlResolversByType {
        return {
            [GraphQlEnum.TYPE_QUERY]: this.#getQueryControllers(),

            [GraphQlEnum.TYPE_MUTATION]: this.#getMutationControllers(),

            // Las tareas de un usuario.
            // User.tasks: solo ejemplo de extender un tipo de otro modulo, no lo consume ningun front (ver tasks.graphql)
            [UsersGraphQlSchemaProvider.TYPE_USER]: {
                tasks: (gqlPayload, user) => SearchTasksByUserGraphQlController.getInstance().invoke(
                    getTrimmedString(user[UsersKeyEnum.USER_UUID]),
                    gqlPayload
                ),
            },
        };
    }

    #getQueryControllers(): GraphQlResolversByType[string] {
        return {
            tasks: (gqlPayload) => SearchTasksGraphQlController.getInstance().invoke(gqlPayload),
            task: (gqlPayload) => GetTaskGraphQlController.getInstance().invoke(gqlPayload),
        };
    }

    #getMutationControllers(): GraphQlResolversByType[string] {
        return {
            createTask: (gqlPayload) => CreateTaskGraphQlController.getInstance().invoke(gqlPayload),
            updateTask: (gqlPayload) => UpdateTaskGraphQlController.getInstance().invoke(gqlPayload),
            deleteTask: (gqlPayload) => DeleteTaskGraphQlController.getInstance().invoke(gqlPayload),
        };
    }
}
