import { HttpCodeEnum } from "/js/Modules/Shared/Domain/Enums/HttpCodeEnum.js";
import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { SessionStorer } from "/js/Modules/Shared/Infrastructure/Components/SessionStorer.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";

import { UsersEventEnum } from "/js/Modules/Users/Domain/Enums/UsersEventEnum.js";

import { TasksEventEnum } from "/js/Modules/Tasks/Domain/Enums/TasksEventEnum.js";
import { TasksMessageEnum } from "/js/Modules/Tasks/Domain/Enums/TasksMessageEnum.js";
import { GetTaskDto } from "/js/Modules/Tasks/Application/GetTask/GetTaskDto.js";
import { GetTaskService } from "/js/Modules/Tasks/Application/GetTask/GetTaskService.js";
import { TasksView } from "/js/Modules/Tasks/Infrastructure/Views/TasksView.js";
import { TasksException } from "/js/Modules/Tasks/Domain/Exceptions/TasksException.js";

/**
 * Detalle de una tarea en el modal de solo lectura (clic en su titulo).
 */
export class GetTaskController {

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
     * @returns {GetTaskController}
     */
    static getInstance() {
        return new GetTaskController();
    }

    init() {
        this.#subscriber.subscribe(TasksEventEnum.TASK_DETAIL_REQUESTED, (request) => this.invoke(request));
    }

    /**
     * @param {{taskUuid: string}} request
     */
    async invoke(request) {
        try {
            const getTaskResultDto = await GetTaskService.getInstance().invoke(
                GetTaskDto.fromPrimitives({
                    apiToken: this.#sessionStorer.getApiToken(),
                    taskUuid: request.taskUuid,
                })
            );
            this.#tasksView.renderTaskDetail(getTaskResultDto.getTask());
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
            console.error("GetTaskController.invoke", error);
            this.#tasksView.renderErrors([TasksMessageEnum.UNEXPECTED_ERROR]);
        }
    }
}
