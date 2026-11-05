import { __ } from "#app/Core/config/global.ts";

import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { AbstractHttpController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractHttpController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { GraphQlException } from "#app/Modules/GraphQl/Domain/Exceptions/GraphQlException.ts";

import { GetTaskDto } from "#app/Modules/Tasks/Application/GetTask/GetTaskDto.ts";
import { GetTaskService } from "#app/Modules/Tasks/Application/GetTask/GetTaskService.ts";
import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";
import { TasksException } from "#app/Modules/Tasks/Domain/Exceptions/TasksException.ts";

import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

export class GetTaskGraphQlController extends AbstractHttpController {

    static getInstance(): GetTaskGraphQlController {
        return new GetTaskGraphQlController();
    }

    /**
     * @throws GraphQlException
     */
    invoke(gqlPayload: Primitives): Primitives {
        try {
            return GetTaskService.getInstance().invoke(
                GetTaskDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    taskUuid: gqlPayload[TasksKeyEnum.TASK_UUID] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof UsersException || e instanceof TasksException) {
                GraphQlException.clientAwareExceptionForWebonyx(e);
            }
            const throwable = getThrowable(e);
            this.logTrait.logException(throwable, "GetTaskGraphQlController::invoke");
            this.mailTrait.sendMailOnThrowable(throwable, "GetTaskGraphQlController::invoke");
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
