import { getPrimitives, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractGraphqlController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractGraphqlController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import { SearchTasksDto } from "#app/Modules/Tasks/Application/SearchTasks/SearchTasksDto.ts";
import { SearchTasksService } from "#app/Modules/Tasks/Application/SearchTasks/SearchTasksService.ts";
import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";
import { TasksException } from "#app/Modules/Tasks/Domain/Exceptions/TasksException.ts";

import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Query tasks (userUuid "") y userTasks / User.tasks (src/Core/Graphql/schema.graphql): busqueda paginada con filtros.
 * El 401 de la autenticacion llega como UsersException.
 */
export class SearchTasksGraphqlController extends AbstractGraphqlController {

    static getInstance(): SearchTasksGraphqlController {
        return new SearchTasksGraphqlController();
    }

    /**
     * args: {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
     * @throws GraphQLError
     */
    invoke(userUuid: string, args: Primitives): Primitives {
        try {
            const filter = getPrimitives(args[TasksKeyEnum.FILTER]);
            return SearchTasksService.getInstance().invoke(
                SearchTasksDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    userUuid: userUuid,
                    taskText: filter[TasksKeyEnum.TASK_TEXT] ?? "",
                    taskStatus: filter[TasksKeyEnum.TASK_STATUS] ?? "",
                    taskType: filter[TasksKeyEnum.TASK_TYPE] ?? "",
                    targetDateFrom: filter[TasksKeyEnum.TARGET_DATE_FROM] ?? "",
                    targetDateTo: filter[TasksKeyEnum.TARGET_DATE_TO] ?? "",
                    page: args[TasksKeyEnum.PAGE] ?? null,
                    pageSize: args[TasksKeyEnum.PAGE_SIZE] ?? null,
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof FieldsException || e instanceof UsersException || e instanceof TasksException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "SearchTasksGraphqlController::invoke");
        }
    }
}
