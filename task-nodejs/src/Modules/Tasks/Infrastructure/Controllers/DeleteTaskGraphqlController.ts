import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractGraphqlController } from "#app/Modules/Graphql/Infrastructure/Controllers/AbstractGraphqlController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { DeleteTaskDto } from "#app/Modules/Tasks/Application/DeleteTask/DeleteTaskDto.ts";
import { DeleteTaskService } from "#app/Modules/Tasks/Application/DeleteTask/DeleteTaskService.ts";
import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";
import { TasksException } from "#app/Modules/Tasks/Domain/Exceptions/TasksException.ts";

import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Mutation deleteTask(task_uuid) (src/Modules/Tasks/Infrastructure/Providers/tasks.graphql). El 401 de la autenticacion llega como UsersException.
 */
export class DeleteTaskGraphqlController extends AbstractGraphqlController {

    static getInstance(): DeleteTaskGraphqlController {
        return new DeleteTaskGraphqlController();
    }

    /**
     * @throws GraphQLError
     */
    invoke(args: Primitives): Primitives {
        try {
            return DeleteTaskService.getInstance().invoke(
                DeleteTaskDto.fromPrimitives({
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
            throw this.getGraphqlUnexpectedError(getThrowable(e), "DeleteTaskGraphqlController::invoke");
        }
    }
}
