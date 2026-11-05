import { __ } from "#app/Core/config/global.ts";

import { getPrimitives, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { AbstractHttpController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractHttpController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import { GraphQlException } from "#app/Modules/GraphQl/Domain/Exceptions/GraphQlException.ts";

import { SearchTasksDto } from "#app/Modules/Tasks/Application/SearchTasks/SearchTasksDto.ts";
import { SearchTasksService } from "#app/Modules/Tasks/Application/SearchTasks/SearchTasksService.ts";
import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";
import { TasksException } from "#app/Modules/Tasks/Domain/Exceptions/TasksException.ts";

import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Query tasks (src/Modules/Tasks/Infrastructure/Providers/tasks.graphql): las tasks del usuario autenticado, paginadas y
 * con filtros. User.tasks (las de un usuario como campo de User) va por SearchTasksByUserGraphQlController.
 * El 401 de la autenticacion llega como UsersException.
 */
export class SearchTasksGraphQlController extends AbstractHttpController {

    static getInstance(): SearchTasksGraphQlController {
        return new SearchTasksGraphQlController();
    }

    /**
     * gqlPayload: {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
     * @throws GraphQlException
     */
    invoke(gqlPayload: Primitives): Primitives {
        try {
            const filter = getPrimitives(gqlPayload[TasksKeyEnum.FILTER]);
            return SearchTasksService.getInstance().invoke(
                SearchTasksDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    taskText: filter[TasksKeyEnum.TASK_TEXT] ?? "",
                    taskStatus: filter[TasksKeyEnum.TASK_STATUS] ?? "",
                    taskType: filter[TasksKeyEnum.TASK_TYPE] ?? "",
                    targetDateFrom: filter[TasksKeyEnum.TARGET_DATE_FROM] ?? "",
                    targetDateTo: filter[TasksKeyEnum.TARGET_DATE_TO] ?? "",
                    page: gqlPayload[TasksKeyEnum.PAGE] ?? null,
                    pageSize: gqlPayload[TasksKeyEnum.PAGE_SIZE] ?? null,
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof FieldsException || e instanceof UsersException || e instanceof TasksException) {
                GraphQlException.clientAwareExceptionForWebonyx(e);
            }
            const throwable = getThrowable(e);
            this.logTrait.logException(throwable, "SearchTasksGraphQlController::invoke");
            this.mailTrait.sendMailOnThrowable(throwable, "SearchTasksGraphQlController::invoke");
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
