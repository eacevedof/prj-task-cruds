import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractGraphqlController } from "#app/Modules/Graphql/Infrastructure/Controllers/AbstractGraphqlController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { GetTaskDto } from "#app/Modules/Tasks/Application/GetTask/GetTaskDto.ts";
import { GetTaskService } from "#app/Modules/Tasks/Application/GetTask/GetTaskService.ts";
import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";
import { TasksException } from "#app/Modules/Tasks/Domain/Exceptions/TasksException.ts";

import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Query task(task_uuid) (src/Modules/Tasks/Infrastructure/Providers/tasks.graphql). El 401 de la autenticacion llega como UsersException.
 */
export class GetTaskGraphqlController extends AbstractGraphqlController {

    static getInstance(): GetTaskGraphqlController {
        return new GetTaskGraphqlController();
    }

    /**
     * @throws GraphQLError
     */
    invoke(args: Primitives): Primitives {
        try {
            return GetTaskService.getInstance().invoke(
                GetTaskDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    taskUuid: args[TasksKeyEnum.TASK_UUID] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof UsersException || e instanceof TasksException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "GetTaskGraphqlController::invoke");
        }
    }
}
