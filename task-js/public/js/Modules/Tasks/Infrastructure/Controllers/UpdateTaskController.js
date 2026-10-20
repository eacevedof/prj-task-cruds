import { HttpCodeEnum } from "/js/Modules/Shared/Domain/Enums/HttpCodeEnum.js";
import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { SessionStorer } from "/js/Modules/Shared/Infrastructure/Components/SessionStorer.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";

import { UsersEventEnum } from "/js/Modules/Users/Domain/Enums/UsersEventEnum.js";

import { TasksEventEnum } from "/js/Modules/Tasks/Domain/Enums/TasksEventEnum.js";
import { TasksMessageEnum } from "/js/Modules/Tasks/Domain/Enums/TasksMessageEnum.js";
import { TasksTextEnum } from "/js/Modules/Tasks/Domain/Enums/TasksTextEnum.js";
import { UpdateTaskDto } from "/js/Modules/Tasks/Application/UpdateTask/UpdateTaskDto.js";
import { UpdateTaskService } from "/js/Modules/Tasks/Application/UpdateTask/UpdateTaskService.js";
import { TasksView } from "/js/Modules/Tasks/Infrastructure/Views/TasksView.js";
import { TasksException } from "/js/Modules/Tasks/Domain/Exceptions/TasksException.js";

export class UpdateTaskController {

    #dispatcher;
    #sessionStorer;
    #subscriber;
    #tasksView;

    constructor() {
        this.#dispatcher = Dispatcher.getInstance();
        this.#sessionStorer = SessionStorer.getInstance();
        this.#subscriber = Subscriber.getInstance();
        this.#tasksView = TasksView.getInstance();
    }

    /**
     * @returns {UpdateTaskController}
     */
    static getInstance() {
        return new UpdateTaskController();
    }

    init() {
        this.#subscriber.subscribe(TasksEventEnum.UPDATE_TASK_REQUESTED, (request) => this.invoke(request));
    }

    /**
     * @param {{taskUuid: string, taskTitle: string, taskStatus: string, taskType: string, targetDate: string, taskDescription: string}} request
     */
    async invoke(request) {
        try {
            await UpdateTaskService.getInstance().invoke(
                UpdateTaskDto.fromPrimitives({
                    apiToken: this.#sessionStorer.getApiToken(),
                    taskUuid: request.taskUuid,
                    taskTitle: request.taskTitle,
                    taskStatus: request.taskStatus,
                    taskType: request.taskType,
                    targetDate: request.targetDate,
                    taskDescription: request.taskDescription,
                })
            );
            this.#tasksView.renderSuccess(TasksTextEnum.UPDATED);
            this.#dispatcher.dispatch(TasksEventEnum.LIST_TASKS_REQUESTED);
        }
        catch (error) {
            if (error instanceof TasksException && error.getCode() === HttpCodeEnum.UNAUTHORIZED) {
                this.#dispatcher.dispatch(UsersEventEnum.SESSION_EXPIRED);
                return;
            }
            if (error instanceof TasksException) {
                this.#tasksView.renderErrors(error.getErrors());
                return;
            }
            console.error("UpdateTaskController.invoke", error);
            this.#tasksView.renderErrors([TasksMessageEnum.UNEXPECTED_ERROR]);
        }
    }
}
