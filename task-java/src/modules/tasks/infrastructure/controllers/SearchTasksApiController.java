package modules.tasks.infrastructure.controllers;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.controllers.AbstractGraphqlController;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.tasks.application.searchtasks.SearchTasksDto;
import modules.tasks.application.searchtasks.SearchTasksService;
import modules.tasks.domain.enums.TasksKeyEnum;
import modules.tasks.domain.exceptions.TasksException;

import modules.users.domain.exceptions.UsersException;

import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Query tasks (userUuid "") y userTasks / User.tasks (src/core/graphql/schema.graphql): busqueda paginada con filtros.
 * El 401 de la autenticacion llega como UsersException.
 */
public final class SearchTasksApiController extends AbstractGraphqlController {

    public SearchTasksApiController() {
        super();
    }

    public static SearchTasksApiController getInstance() {
        return new SearchTasksApiController();
    }

    /**
     * args: {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> invoke(String userUuid, Map<String, Object> args) {
        try {
            Map<String, Object> filter = Primitives.getPrimitives(args.get(TasksKeyEnum.FILTER));
            return SearchTasksService.getInstance().invoke(
                SearchTasksDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail(),
                    Primitives.of(
                        "userUuid", userUuid,
                        "taskText", getValueOrDefault(filter, TasksKeyEnum.TASK_TEXT, ""),
                        "taskStatus", getValueOrDefault(filter, TasksKeyEnum.TASK_STATUS, ""),
                        "taskType", getValueOrDefault(filter, TasksKeyEnum.TASK_TYPE, ""),
                        "targetDateFrom", getValueOrDefault(filter, TasksKeyEnum.TARGET_DATE_FROM, ""),
                        "targetDateTo", getValueOrDefault(filter, TasksKeyEnum.TARGET_DATE_TO, ""),
                        "page", args.get(TasksKeyEnum.PAGE),
                        "pageSize", args.get(TasksKeyEnum.PAGE_SIZE)
                    )
                ))
            ).toArray();
        }
        catch (FieldsException | UsersException | TasksException ex) {
            throw getGraphqlDomainError(ex);
        }
        catch (Exception ex) {
            throw getGraphqlUnexpectedError(ex, "SearchTasksApiController::invoke");
        }
    }
}
