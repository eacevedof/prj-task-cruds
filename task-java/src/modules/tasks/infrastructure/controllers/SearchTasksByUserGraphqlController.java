package modules.tasks.infrastructure.controllers;

import java.util.Map;

import modules.graphql.infrastructure.controllers.AbstractGraphqlController;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.tasks.application.searchtasks.SearchTasksDto;
import modules.tasks.application.searchtasks.SearchTasksService;
import modules.tasks.domain.enums.TasksKeyEnum;
import modules.tasks.domain.exceptions.TasksException;

import modules.users.domain.exceptions.UsersException;

import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Query userTasks y campo User.tasks (src/modules/tasks/infrastructure/providers/tasks.graphql): las tasks de un
 * usuario, paginadas y con filtros. El uuid llega aparte porque en User.tasks sale del User padre, no de los args.
 * Un usuario que no es el autenticado responde 404. El 401 de la autenticacion llega como UsersException.
 */
public final class SearchTasksByUserGraphqlController extends AbstractGraphqlController {

    public SearchTasksByUserGraphqlController() {
        super();
    }

    public static SearchTasksByUserGraphqlController getInstance() {
        return new SearchTasksByUserGraphqlController();
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
            throw getGraphqlUnexpectedError(ex, "SearchTasksByUserGraphqlController::invoke");
        }
    }
}
