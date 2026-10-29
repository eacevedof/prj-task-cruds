import { readFileSync } from "node:fs";

import { getTrimmedString } from "#app/Modules/Shared/Application/Primitives.ts";

import { GraphqlEnum } from "#app/Modules/Graphql/Domain/Enums/GraphqlEnum.ts";
import type {
    GraphqlResolversByType,
    InterfaceGraphqlSchemaProvider,
} from "#app/Modules/Graphql/Infrastructure/Schema/InterfaceGraphqlSchemaProvider.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";

import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";
import { CreateTaskGraphqlController } from "#app/Modules/Tasks/Infrastructure/Controllers/CreateTaskGraphqlController.ts";
import { DeleteTaskGraphqlController } from "#app/Modules/Tasks/Infrastructure/Controllers/DeleteTaskGraphqlController.ts";
import { GetTaskGraphqlController } from "#app/Modules/Tasks/Infrastructure/Controllers/GetTaskGraphqlController.ts";
import { SearchTasksByUserGraphqlController } from "#app/Modules/Tasks/Infrastructure/Controllers/SearchTasksByUserGraphqlController.ts";
import { SearchTasksGraphqlController } from "#app/Modules/Tasks/Infrastructure/Controllers/SearchTasksGraphqlController.ts";
import { UpdateTaskGraphqlController } from "#app/Modules/Tasks/Infrastructure/Controllers/UpdateTaskGraphqlController.ts";

/**
 * Parte de tasks en /graphql: tasks.graphql (extiende Query, Mutation y User de users) y sus resolvers, incluido
 * User.tasks, que cuelga de User pero es de tasks. Va despues de UsersGraphqlSchemaProvider en el registro.
 */
export class TasksGraphqlSchemaProvider implements InterfaceGraphqlSchemaProvider {

    static getInstance(): TasksGraphqlSchemaProvider {
        return new TasksGraphqlSchemaProvider();
    }

    getSdl(): string {
        return readFileSync(new URL("tasks.graphql", import.meta.url), "utf8");
    }

    getResolversByType(): GraphqlResolversByType {
        return {
            [GraphqlEnum.TYPE_QUERY]: {
                tasks: (args) => SearchTasksGraphqlController.getInstance().invoke(args),
                task: (args) => GetTaskGraphqlController.getInstance().invoke(args),
                userTasks: (args) => SearchTasksByUserGraphqlController.getInstance().invoke(
                    getTrimmedString(args[TasksKeyEnum.USER_UUID]),
                    args
                ),
            },
            [GraphqlEnum.TYPE_MUTATION]: {
                createTask: (args) => CreateTaskGraphqlController.getInstance().invoke(args),
                updateTask: (args) => UpdateTaskGraphqlController.getInstance().invoke(args),
                deleteTask: (args) => DeleteTaskGraphqlController.getInstance().invoke(args),
            },
            [GraphqlEnum.TYPE_USER]: {
                tasks: (args, user) => SearchTasksByUserGraphqlController.getInstance().invoke(
                    getTrimmedString(user[UsersKeyEnum.USER_UUID]),
                    args
                ),
            },
        };
    }
}
