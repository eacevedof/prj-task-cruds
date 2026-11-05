import { __ } from "#app/Core/config/global.ts";

import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { AbstractHttpController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractHttpController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import { GraphQlException } from "#app/Modules/GraphQl/Domain/Exceptions/GraphQlException.ts";

import { UpdateTaskDto } from "#app/Modules/Tasks/Application/UpdateTask/UpdateTaskDto.ts";
import { UpdateTaskService } from "#app/Modules/Tasks/Application/UpdateTask/UpdateTaskService.ts";
import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";
import { TasksException } from "#app/Modules/Tasks/Domain/Exceptions/TasksException.ts";

import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Mutation updateTask (src/Modules/Tasks/Infrastructure/Providers/tasks.graphql). El 401 de la autenticacion llega como UsersException.
 */
export class UpdateTaskGraphQlController extends AbstractHttpController {

    static getInstance(): UpdateTaskGraphQlController {
        return new UpdateTaskGraphQlController();
    }

    /**
     * @throws GraphQlException
     */
    invoke(gqlPayload: Primitives): Primitives {
        try {
            return UpdateTaskService.getInstance().invoke(
                UpdateTaskDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    taskUuid: gqlPayload[TasksKeyEnum.TASK_UUID] ?? "",
                    taskTitle: gqlPayload[TasksKeyEnum.TASK_TITLE] ?? "",
                    taskStatus: gqlPayload[TasksKeyEnum.TASK_STATUS] ?? "",
                    taskType: gqlPayload[TasksKeyEnum.TASK_TYPE] ?? "",
                    targetDate: gqlPayload[TasksKeyEnum.TARGET_DATE] ?? "",
                    taskDescription: gqlPayload[TasksKeyEnum.TASK_DESCRIPTION] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof FieldsException || e instanceof UsersException || e instanceof TasksException) {
                GraphQlException.clientAwareExceptionForWebonyx(e);
            }
            const throwable = getThrowable(e);
            this.logTrait.logException(throwable, "UpdateTaskGraphQlController::invoke");
            this.mailTrait.sendMailOnThrowable(throwable, "UpdateTaskGraphQlController::invoke");
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
