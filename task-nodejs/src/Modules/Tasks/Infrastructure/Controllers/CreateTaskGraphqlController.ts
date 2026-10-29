import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractGraphqlController } from "#app/Modules/Graphql/Infrastructure/Controllers/AbstractGraphqlController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import { CreateTaskDto } from "#app/Modules/Tasks/Application/CreateTask/CreateTaskDto.ts";
import { CreateTaskService } from "#app/Modules/Tasks/Application/CreateTask/CreateTaskService.ts";
import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";
import { TasksException } from "#app/Modules/Tasks/Domain/Exceptions/TasksException.ts";

import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Mutation createTask (src/Modules/Tasks/Infrastructure/Providers/tasks.graphql): el dueno es el usuario del token.
 * El 401 de la autenticacion llega como UsersException.
 */
export class CreateTaskGraphqlController extends AbstractGraphqlController {

    static getInstance(): CreateTaskGraphqlController {
        return new CreateTaskGraphqlController();
    }

    /**
     * @throws GraphQLError
     */
    invoke(args: Primitives): Primitives {
        try {
            return CreateTaskService.getInstance().invoke(
                CreateTaskDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    taskTitle: args[TasksKeyEnum.TASK_TITLE] ?? "",
                    taskStatus: args[TasksKeyEnum.TASK_STATUS] ?? "",
                    taskType: args[TasksKeyEnum.TASK_TYPE] ?? "",
                    targetDate: args[TasksKeyEnum.TARGET_DATE] ?? "",
                    taskDescription: args[TasksKeyEnum.TASK_DESCRIPTION] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof FieldsException || e instanceof UsersException || e instanceof TasksException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "CreateTaskGraphqlController::invoke");
        }
    }
}
