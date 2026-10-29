package modules.tasks.infrastructure.controllers;

import java.util.Map;

import modules.graphql.infrastructure.controllers.AbstractGraphqlController;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.tasks.application.createtask.CreateTaskDto;
import modules.tasks.application.createtask.CreateTaskService;
import modules.tasks.domain.enums.TasksKeyEnum;
import modules.tasks.domain.exceptions.TasksException;

import modules.users.domain.exceptions.UsersException;

import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Mutation createTask (src/modules/tasks/infrastructure/providers/tasks.graphql): el dueno es el usuario del token.
 * El 401 de la autenticacion llega como UsersException.
 */
public final class CreateTaskGraphqlController extends AbstractGraphqlController {

    public CreateTaskGraphqlController() {
        super();
    }

    public static CreateTaskGraphqlController getInstance() {
        return new CreateTaskGraphqlController();
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> invoke(Map<String, Object> args) {
        try {
            return CreateTaskService.getInstance().invoke(
                CreateTaskDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail(),
                    Primitives.of(
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
            throw getGraphqlUnexpectedError(ex, "CreateTaskGraphqlController::invoke");
        }
    }
}
