package modules.tasks.infrastructure.controllers;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.controllers.AbstractGraphqlController;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.tasks.application.updatetask.UpdateTaskDto;
import modules.tasks.application.updatetask.UpdateTaskService;
import modules.tasks.domain.enums.TasksKeyEnum;
import modules.tasks.domain.exceptions.TasksException;

import modules.users.domain.exceptions.UsersException;

import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Mutation updateTask (src/core/graphql/schema.graphql). El 401 de la autenticacion llega como UsersException.
 */
public final class UpdateTaskGraphqlController extends AbstractGraphqlController {

    public UpdateTaskGraphqlController() {
        super();
    }

    public static UpdateTaskGraphqlController getInstance() {
        return new UpdateTaskGraphqlController();
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> invoke(Map<String, Object> args) {
        try {
            return UpdateTaskService.getInstance().invoke(
                UpdateTaskDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail(),
                    Primitives.of(
                        "taskUuid", getValueOrDefault(args, TasksKeyEnum.TASK_UUID, ""),
                        "taskTitle", getValueOrDefault(args, TasksKeyEnum.TASK_TITLE, ""),
                        "taskStatus", getValueOrDefault(args, TasksKeyEnum.TASK_STATUS, ""),
                        "taskType", getValueOrDefault(args, TasksKeyEnum.TASK_TYPE, ""),
                        "targetDate", getValueOrDefault(args, TasksKeyEnum.TARGET_DATE, ""),
                        "taskDescription", getValueOrDefault(args, TasksKeyEnum.TASK_DESCRIPTION, "")
                    )
                ))
            ).toArray();
        }
        catch (FieldsException | UsersException | TasksException ex) {
            throw getGraphqlDomainError(ex);
        }
        catch (Exception ex) {
            throw getGraphqlUnexpectedError(ex, "UpdateTaskGraphqlController::invoke");
        }
    }
}
