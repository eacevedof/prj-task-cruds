package modules.tasks.infrastructure.controllers;

import java.util.Map;

import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.controllers.AbstractHttpController;

import modules.graphql.domain.exceptions.GraphQlException;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.tasks.application.createtask.CreateTaskDto;
import modules.tasks.application.createtask.CreateTaskService;
import modules.tasks.domain.enums.TasksKeyEnum;
import modules.tasks.domain.exceptions.TasksException;

import modules.users.domain.exceptions.UsersException;

import static core.config.Global.__;
import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Mutation createTask (src/modules/tasks/infrastructure/providers/tasks.graphql): el dueno es el usuario del token.
 * El 401 de la autenticacion llega como UsersException.
 */
public final class CreateTaskGraphQlController extends AbstractHttpController {

    public CreateTaskGraphQlController() {
        super();
    }

    public static CreateTaskGraphQlController getInstance() {
        return new CreateTaskGraphQlController();
    }

    /**
     * @throws GraphQlException
     */
    public Map<String, Object> invoke(Map<String, Object> gqlPayload) {
        try {
            return CreateTaskService.getInstance().invoke(
                CreateTaskDto.fromPrimitives(Primitives.merge(
                    httpRequest.getMetadata(),
                    getAuthUserOrFail(),
                    Primitives.of(
                        "taskTitle", getValueOrDefault(gqlPayload, TasksKeyEnum.TASK_TITLE, ""),
                        "taskStatus", getValueOrDefault(gqlPayload, TasksKeyEnum.TASK_STATUS, ""),
                        "taskType", getValueOrDefault(gqlPayload, TasksKeyEnum.TASK_TYPE, ""),
                        "targetDate", getValueOrDefault(gqlPayload, TasksKeyEnum.TARGET_DATE, ""),
                        "taskDescription", getValueOrDefault(gqlPayload, TasksKeyEnum.TASK_DESCRIPTION, "")
                    )
                ))
            ).toArray();
        }
        catch (FieldsException | UsersException | TasksException ex) {
            throw GraphQlException.clientAwareExceptionForWebonyx(ex);
        }
        catch (Exception ex) {
            logTrait.logException(ex, "CreateTaskGraphQlController::invoke");
            mailTrait.sendMailOnThrowable(ex, "CreateTaskGraphQlController::invoke");
            throw GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED));
        }
    }
}
