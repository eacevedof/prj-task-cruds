package modules.tasks.infrastructure.controllers;

import java.util.Map;

import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.controllers.AbstractHttpController;

import modules.graphql.domain.exceptions.GraphQlException;

import modules.shared.application.Primitives;
import modules.tasks.application.deletetask.DeleteTaskDto;
import modules.tasks.application.deletetask.DeleteTaskService;
import modules.tasks.domain.enums.TasksKeyEnum;
import modules.tasks.domain.exceptions.TasksException;

import modules.users.domain.exceptions.UsersException;

import static core.config.Global.__;
import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Mutation deleteTask(task_uuid) (src/modules/tasks/infrastructure/providers/tasks.graphql). El 401 de la autenticacion llega como UsersException.
 */
public final class DeleteTaskGraphQlController extends AbstractHttpController {

    public DeleteTaskGraphQlController() {
        super();
    }

    public static DeleteTaskGraphQlController getInstance() {
        return new DeleteTaskGraphQlController();
    }

    /**
     * @throws GraphQlException
     */
    public Map<String, Object> invoke(Map<String, Object> gqlPayload) {
        try {
            return DeleteTaskService.getInstance().invoke(
                DeleteTaskDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail(),
                    Primitives.of("taskUuid", getValueOrDefault(gqlPayload, TasksKeyEnum.TASK_UUID, ""))
                ))
            ).toArray();
        }
        catch (UsersException | TasksException ex) {
            throw GraphQlException.clientAwareExceptionForWebonyx(ex);
        }
        catch (Exception ex) {
            logTrait.logException(ex, "DeleteTaskGraphQlController::invoke");
            mailTrait.sendMailOnThrowable(ex, "DeleteTaskGraphQlController::invoke");
            throw GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
