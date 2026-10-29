package modules.tasks.infrastructure.controllers;

import java.util.Map;

import modules.graphql.infrastructure.controllers.AbstractGraphqlController;

import modules.shared.application.Primitives;
import modules.tasks.application.deletetask.DeleteTaskDto;
import modules.tasks.application.deletetask.DeleteTaskService;
import modules.tasks.domain.enums.TasksKeyEnum;
import modules.tasks.domain.exceptions.TasksException;

import modules.users.domain.exceptions.UsersException;

import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Mutation deleteTask(task_uuid) (src/modules/tasks/infrastructure/providers/tasks.graphql). El 401 de la autenticacion llega como UsersException.
 */
public final class DeleteTaskGraphqlController extends AbstractGraphqlController {

    public DeleteTaskGraphqlController() {
        super();
    }

    public static DeleteTaskGraphqlController getInstance() {
        return new DeleteTaskGraphqlController();
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> invoke(Map<String, Object> args) {
        try {
            return DeleteTaskService.getInstance().invoke(
                DeleteTaskDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail(),
                    Primitives.of("taskUuid", getValueOrDefault(args, TasksKeyEnum.TASK_UUID, ""))
                ))
            ).toArray();
        }
        catch (UsersException | TasksException ex) {
            throw getGraphqlDomainError(ex);
        }
        catch (Exception ex) {
            throw getGraphqlUnexpectedError(ex, "DeleteTaskGraphqlController::invoke");
        }
    }
}
