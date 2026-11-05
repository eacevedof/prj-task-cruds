import { __ } from "#app/Core/config/global.ts";

import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { AbstractHttpController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractHttpController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import { GraphQlException } from "#app/Modules/GraphQl/Domain/Exceptions/GraphQlException.ts";

import { CreateTaskDto } from "#app/Modules/Tasks/Application/CreateTask/CreateTaskDto.ts";
import { CreateTaskService } from "#app/Modules/Tasks/Application/CreateTask/CreateTaskService.ts";
import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";
import { TasksException } from "#app/Modules/Tasks/Domain/Exceptions/TasksException.ts";

import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

export class CreateTaskGraphQlController extends AbstractHttpController {

    static getInstance(): CreateTaskGraphQlController {
        return new CreateTaskGraphQlController();
    }

    /**
     * @throws GraphQlException
     */
    invoke(gqlPayload: Primitives): Primitives {
        try {
            return CreateTaskService.getInstance().invoke(
                CreateTaskDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
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
            this.logTrait.logException(throwable, "CreateTaskGraphQlController::invoke");
            this.mailTrait.sendMailOnThrowable(throwable, "CreateTaskGraphQlController::invoke");
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
