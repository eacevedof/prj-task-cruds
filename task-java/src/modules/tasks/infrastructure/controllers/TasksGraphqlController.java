package modules.tasks.infrastructure.controllers;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.controllers.AbstractGraphqlController;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.tasks.application.createtask.CreateTaskDto;
import modules.tasks.application.createtask.CreateTaskService;
import modules.tasks.application.deletetask.DeleteTaskDto;
import modules.tasks.application.deletetask.DeleteTaskService;
import modules.tasks.application.gettask.GetTaskDto;
import modules.tasks.application.gettask.GetTaskService;
import modules.tasks.application.searchtasks.SearchTasksDto;
import modules.tasks.application.searchtasks.SearchTasksService;
import modules.tasks.application.updatetask.UpdateTaskDto;
import modules.tasks.application.updatetask.UpdateTaskService;
import modules.tasks.domain.enums.TasksKeyEnum;
import modules.tasks.domain.exceptions.TasksException;

import modules.users.domain.exceptions.UsersException;

import static modules.shared.application.Primitives.getValueOrDefault;

/**
 * Resolvers GraphQL de tasks (src/core/graphql/schema.graphql).
 * El 401 de la autenticacion llega como UsersException.
 */
public final class TasksGraphqlController extends AbstractGraphqlController {

    public TasksGraphqlController() {
        super();
    }

    public static TasksGraphqlController getInstance() {
        return new TasksGraphqlController();
    }

    /**
     * Query tasks (userUuid "") y userTasks / User.tasks: busqueda paginada con filtros.
     * args: {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> searchTasks(String userUuid, Map<String, Object> args) {
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
            throw getGraphqlUnexpectedError(ex, "TasksGraphqlController::searchTasks");
        }
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> getTask(Map<String, Object> args) {
        try {
            return GetTaskService.getInstance().invoke(
                GetTaskDto.fromPrimitives(Primitives.merge(
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
            throw getGraphqlUnexpectedError(ex, "TasksGraphqlController::getTask");
        }
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> createTask(Map<String, Object> args) {
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
            throw getGraphqlUnexpectedError(ex, "TasksGraphqlController::createTask");
        }
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> updateTask(Map<String, Object> args) {
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
            throw getGraphqlUnexpectedError(ex, "TasksGraphqlController::updateTask");
        }
    }

    /**
     * @throws graphql.GraphqlErrorException
     */
    public Map<String, Object> deleteTask(Map<String, Object> args) {
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
            throw getGraphqlUnexpectedError(ex, "TasksGraphqlController::deleteTask");
        }
    }
}
