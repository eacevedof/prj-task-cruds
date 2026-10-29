import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractGraphqlController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractGraphqlController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import { UpdateTaskDto } from "#app/Modules/Tasks/Application/UpdateTask/UpdateTaskDto.ts";
import { UpdateTaskService } from "#app/Modules/Tasks/Application/UpdateTask/UpdateTaskService.ts";
import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";
import { TasksException } from "#app/Modules/Tasks/Domain/Exceptions/TasksException.ts";

import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Mutation updateTask (src/Core/Graphql/schema.graphql). El 401 de la autenticacion llega como UsersException.
 */
export class UpdateTaskGraphqlController extends AbstractGraphqlController {

    static getInstance(): UpdateTaskGraphqlController {
        return new UpdateTaskGraphqlController();
    }

    /**
     * @throws GraphQLError
     */
    invoke(args: Primitives): Primitives {
        try {
            return UpdateTaskService.getInstance().invoke(
                UpdateTaskDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    taskUuid: args[TasksKeyEnum.TASK_UUID] ?? "",
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
            throw this.getGraphqlUnexpectedError(getThrowable(e), "UpdateTaskGraphqlController::invoke");
        }
    }
}
