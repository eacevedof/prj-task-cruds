package modules.tasks.infrastructure.controllers;

import java.util.Map;

import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.controllers.AbstractHttpController;

import modules.graphql.domain.exceptions.GraphQlException;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.tasks.application.searchtasks.SearchTasksDto;
import modules.tasks.application.searchtasks.SearchTasksService;
import modules.tasks.domain.enums.TasksKeyEnum;
import modules.tasks.domain.exceptions.TasksException;

import modules.users.domain.exceptions.UsersException;

import static core.config.Global.__;
import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Query tasks (src/modules/tasks/infrastructure/providers/tasks.graphql): las tasks del usuario autenticado, paginadas y
 * con filtros. User.tasks (las de un usuario como campo de User) va por SearchTasksByUserGraphQlController.
 * El 401 de la autenticacion llega como UsersException.
 */
public final class SearchTasksGraphQlController extends AbstractHttpController {

    public SearchTasksGraphQlController() {
        super();
    }

    public static SearchTasksGraphQlController getInstance() {
        return new SearchTasksGraphQlController();
    }

    /**
     * gqlPayload: {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
     * @throws GraphQlException
     */
    public Map<String, Object> invoke(Map<String, Object> gqlPayload) {
        try {
            Map<String, Object> filter = Primitives.getPrimitives(gqlPayload.get(TasksKeyEnum.FILTER));
            return SearchTasksService.getInstance().invoke(
                SearchTasksDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail(),
                    Primitives.of(
                        "taskText", getValueOrDefault(filter, TasksKeyEnum.TASK_TEXT, ""),
                        "taskStatus", getValueOrDefault(filter, TasksKeyEnum.TASK_STATUS, ""),
                        "taskType", getValueOrDefault(filter, TasksKeyEnum.TASK_TYPE, ""),
                        "targetDateFrom", getValueOrDefault(filter, TasksKeyEnum.TARGET_DATE_FROM, ""),
                        "targetDateTo", getValueOrDefault(filter, TasksKeyEnum.TARGET_DATE_TO, ""),
                        "page", gqlPayload.get(TasksKeyEnum.PAGE),
                        "pageSize", gqlPayload.get(TasksKeyEnum.PAGE_SIZE)
                    )
                ))
            ).toArray();
        }
        catch (FieldsException | UsersException | TasksException ex) {
            throw GraphQlException.clientAwareExceptionForWebonyx(ex);
        }
        catch (Exception ex) {
            logTrait.logException(ex, "SearchTasksGraphQlController::invoke");
            mailTrait.sendMailOnThrowable(ex, "SearchTasksGraphQlController::invoke");
            throw GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
