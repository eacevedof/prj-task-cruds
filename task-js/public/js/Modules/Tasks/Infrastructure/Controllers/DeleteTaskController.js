import { HttpCodeEnum } from "/js/Modules/Shared/Domain/Enums/HttpCodeEnum.js";
import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { SessionStorer } from "/js/Modules/Shared/Infrastructure/Components/SessionStorer.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";

import { UsersEventEnum } from "/js/Modules/Users/Domain/Enums/UsersEventEnum.js";

import { TasksEventEnum } from "/js/Modules/Tasks/Domain/Enums/TasksEventEnum.js";
import { TasksMessageEnum } from "/js/Modules/Tasks/Domain/Enums/TasksMessageEnum.js";
import { TasksTextEnum } from "/js/Modules/Tasks/Domain/Enums/TasksTextEnum.js";
import { DeleteTaskDto } from "/js/Modules/Tasks/Application/DeleteTask/DeleteTaskDto.js";
import { DeleteTaskService } from "/js/Modules/Tasks/Application/DeleteTask/DeleteTaskService.js";
import { TasksView } from "/js/Modules/Tasks/Infrastructure/Views/TasksView.js";
import { TasksException } from "/js/Modules/Tasks/Domain/Exceptions/TasksException.js";

export class DeleteTaskController {

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
     * @returns {DeleteTaskController}
     */
    static getInstance() {
        return new DeleteTaskController();
    }

    init() {
        this.#subscriber.subscribe(TasksEventEnum.DELETE_TASK_REQUESTED, (request) => this.invoke(request));
    }

    /**
     * @param {{taskUuid: string}} request
     */
    async invoke(request) {
        try {
            await DeleteTaskService.getInstance().invoke(
                DeleteTaskDto.fromPrimitives({
                    apiToken: this.#sessionStorer.getApiToken(),
                    taskUuid: request.taskUuid,
                })
            );
            this.#tasksView.renderSuccess(TasksTextEnum.DELETED);
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
            console.error("DeleteTaskController.invoke", error);
            this.#tasksView.renderErrors([TasksMessageEnum.UNEXPECTED_ERROR]);
        }
    }
}
