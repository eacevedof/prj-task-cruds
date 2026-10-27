import { getPrimitives, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractGraphqlController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractGraphqlController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import { CreateTaskDto } from "#app/Modules/Tasks/Application/CreateTask/CreateTaskDto.ts";
import { CreateTaskService } from "#app/Modules/Tasks/Application/CreateTask/CreateTaskService.ts";
import { DeleteTaskDto } from "#app/Modules/Tasks/Application/DeleteTask/DeleteTaskDto.ts";
import { DeleteTaskService } from "#app/Modules/Tasks/Application/DeleteTask/DeleteTaskService.ts";
import { GetTaskDto } from "#app/Modules/Tasks/Application/GetTask/GetTaskDto.ts";
import { GetTaskService } from "#app/Modules/Tasks/Application/GetTask/GetTaskService.ts";
import { SearchTasksDto } from "#app/Modules/Tasks/Application/SearchTasks/SearchTasksDto.ts";
import { SearchTasksService } from "#app/Modules/Tasks/Application/SearchTasks/SearchTasksService.ts";
import { UpdateTaskDto } from "#app/Modules/Tasks/Application/UpdateTask/UpdateTaskDto.ts";
import { UpdateTaskService } from "#app/Modules/Tasks/Application/UpdateTask/UpdateTaskService.ts";
import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";
import { TasksException } from "#app/Modules/Tasks/Domain/Exceptions/TasksException.ts";

import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * Resolvers GraphQL de tasks (src/Core/Graphql/schema.graphql).
 * El 401 de la autenticacion llega como UsersException.
 */
export class TasksGraphqlController extends AbstractGraphqlController {

    static getInstance(): TasksGraphqlController {
        return new TasksGraphqlController();
    }

    /**
     * Query tasks (userUuid "") y userTasks / User.tasks: busqueda paginada con filtros.
     * args: {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
     * @throws GraphQLError
     */
    searchTasks(userUuid: string, args: Primitives): Primitives {
        try {
            const filter = getPrimitives(args[TasksKeyEnum.FILTER]);
            return SearchTasksService.getInstance().invoke(
                SearchTasksDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    userUuid: userUuid,
                    taskText: filter[TasksKeyEnum.TASK_TEXT] ?? "",
                    taskStatus: filter[TasksKeyEnum.TASK_STATUS] ?? "",
                    taskType: filter[TasksKeyEnum.TASK_TYPE] ?? "",
                    targetDateFrom: filter[TasksKeyEnum.TARGET_DATE_FROM] ?? "",
                    targetDateTo: filter[TasksKeyEnum.TARGET_DATE_TO] ?? "",
                    page: args[TasksKeyEnum.PAGE] ?? null,
                    pageSize: args[TasksKeyEnum.PAGE_SIZE] ?? null,
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof FieldsException || e instanceof UsersException || e instanceof TasksException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "TasksGraphqlController::searchTasks");
        }
    }

    /**
     * @throws GraphQLError
     */
    getTask(args: Primitives): Primitives {
        try {
            return GetTaskService.getInstance().invoke(
                GetTaskDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    taskUuid: args[TasksKeyEnum.TASK_UUID] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof UsersException || e instanceof TasksException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "TasksGraphqlController::getTask");
        }
    }

    /**
     * @throws GraphQLError
     */
    createTask(args: Primitives): Primitives {
        try {
            return CreateTaskService.getInstance().invoke(
                CreateTaskDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    taskTitle: args[TasksKeyEnum.TASK_TITLE] ?? "",
                    taskStatus: args[TasksKeyEnum.TASK_STATUS] ?? "",
                    taskType: args[TasksKeyEnum.TASK_TYPE] ?? "",
                    targetDate: args[TasksKeyEnum.TARGET_DATE] ?? "",
                    taskDescription: args[TasksKeyEnum.TASK_DESCRIPTION] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof FieldsException || e instanceof UsersException || e instanceof TasksException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "TasksGraphqlController::createTask");
        }
    }

    /**
     * @throws GraphQLError
     */
    updateTask(args: Primitives): Primitives {
        try {
            return UpdateTaskService.getInstance().invoke(
                UpdateTaskDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    taskUuid: args[TasksKeyEnum.TASK_UUID] ?? "",
                    taskTitle: args[TasksKeyEnum.TASK_TITLE] ?? "",
                    taskStatus: args[TasksKeyEnum.TASK_STATUS] ?? "",
                    taskType: args[TasksKeyEnum.TASK_TYPE] ?? "",
                    targetDate: args[TasksKeyEnum.TARGET_DATE] ?? "",
                    taskDescription: args[TasksKeyEnum.TASK_DESCRIPTION] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof FieldsException || e instanceof UsersException || e instanceof TasksException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "TasksGraphqlController::updateTask");
        }
    }

    /**
     * @throws GraphQLError
     */
    deleteTask(args: Primitives): Primitives {
        try {
            return DeleteTaskService.getInstance().invoke(
                DeleteTaskDto.fromPrimitives({
                    ...this.httpRequest.getMetadata(),
                    ...this.getAuthUserOrFail(),
                    taskUuid: args[TasksKeyEnum.TASK_UUID] ?? "",
                })
            ).toArray();
        }
        catch (e) {
            if (e instanceof UsersException || e instanceof TasksException) {
                throw this.getGraphqlDomainError(e);
            }
            throw this.getGraphqlUnexpectedError(getThrowable(e), "TasksGraphqlController::deleteTask");
        }
    }
}
