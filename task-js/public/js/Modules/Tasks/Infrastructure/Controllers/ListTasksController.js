import { HttpCodeEnum } from "/js/Modules/Shared/Domain/Enums/HttpCodeEnum.js";
import { SharedEventEnum } from "/js/Modules/Shared/Domain/Enums/SharedEventEnum.js";
import { StorageKeyEnum } from "/js/Modules/Shared/Domain/Enums/StorageKeyEnum.js";
import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { LocalStorer } from "/js/Modules/Shared/Infrastructure/Components/LocalStorer.js";
import { SessionStorer } from "/js/Modules/Shared/Infrastructure/Components/SessionStorer.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";

import { UsersEventEnum } from "/js/Modules/Users/Domain/Enums/UsersEventEnum.js";

import { TasksEventEnum } from "/js/Modules/Tasks/Domain/Enums/TasksEventEnum.js";
import { TasksMessageEnum } from "/js/Modules/Tasks/Domain/Enums/TasksMessageEnum.js";
import { TasksPaginationEnum } from "/js/Modules/Tasks/Domain/Enums/TasksPaginationEnum.js";
import { ListTasksDto } from "/js/Modules/Tasks/Application/ListTasks/ListTasksDto.js";
import { ListTasksService } from "/js/Modules/Tasks/Application/ListTasks/ListTasksService.js";
import { TasksView } from "/js/Modules/Tasks/Infrastructure/Views/TasksView.js";
import { TasksException } from "/js/Modules/Tasks/Domain/Exceptions/TasksException.js";

const EMPTY_FILTERS = Object.freeze({
    taskText: "",
    targetDateFrom: "",
    targetDateTo: "",
});

/**
 * Lista paginada (TasksPaginationEnum.PAGE_SIZE por pagina) y filtrada de tareas. Los filtros se guardan en
 * localStorage (se aplican al volver a entrar); la pagina actual solo en memoria: editar o borrar no te saca de ella, cambiar un filtro
 * te lleva a la primera. Tambien limpia la pantalla al entrar y salir: Tasks escucha a Users (no al reves).
 */
export class ListTasksController {

    #dispatcher;
    #localStorer;
    #sessionStorer;
    #subscriber;
    #tasksView;
    #currentPage = TasksPaginationEnum.FIRST_PAGE;

    constructor() {
        this.#dispatcher = Dispatcher.getInstance();
        this.#localStorer = LocalStorer.getInstance();
        this.#sessionStorer = SessionStorer.getInstance();
        this.#subscriber = Subscriber.getInstance();
        this.#tasksView = TasksView.getInstance();
    }

    /**
     * @returns {ListTasksController}
     */
    static getInstance() {
        return new ListTasksController();
    }

    init() {
        this.#subscriber.subscribe(TasksEventEnum.LIST_TASKS_REQUESTED, (request) => this.invoke(request));
        this.#subscriber.subscribe(UsersEventEnum.USER_LOGGED_IN, () => {
            this.#tasksView.reset();
            this.invoke({ page: TasksPaginationEnum.FIRST_PAGE });
        });
        this.#subscriber.subscribe(UsersEventEnum.USER_LOGGED_OUT, () => this.#tasksView.reset());
        this.#subscriber.subscribe(SharedEventEnum.LANGUAGE_CHANGED, () => this.#tasksView.renderTranslations());
    }

    /**
     * @param {{filters?: {taskText: string, targetDateFrom: string, targetDateTo: string}, page?: number}} request
     *     sin filters: los guardados; sin page: la actual
     */
    async invoke(request) {
        try {
            const filters = request.filters ?? this.#getSavedFilters();
            this.#saveFilters(filters);
            this.#tasksView.renderFilters(filters);

            const listTasksResultDto = await this.#getListTasksResultDto(filters, request.page ?? this.#currentPage);
            // al borrar la ultima tarea de la ultima pagina, esa pagina ya no existe: se va a la nueva ultima
            const isPageOverflow = !listTasksResultDto.getTasks().length && listTasksResultDto.getPage() > listTasksResultDto.getTotalPages();
            const finalListTasksResultDto = isPageOverflow && listTasksResultDto.getTotalPages()
                ? await this.#getListTasksResultDto(filters, listTasksResultDto.getTotalPages())
                : listTasksResultDto;

            this.#currentPage = finalListTasksResultDto.getPage() || TasksPaginationEnum.FIRST_PAGE;
            this.#tasksView.renderTasks(finalListTasksResultDto.getTasks());
            this.#tasksView.renderPagination({
                page: finalListTasksResultDto.getPage(),
                totalPages: finalListTasksResultDto.getTotalPages(),
                totalItems: finalListTasksResultDto.getTotalItems(),
            });
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
            console.error("ListTasksController.invoke", error);
            this.#tasksView.renderErrors([TasksMessageEnum.UNEXPECTED_ERROR]);
        }
    }

    async #getListTasksResultDto(filters, page) {
        return ListTasksService.getInstance().invoke(
            ListTasksDto.fromPrimitives({
                apiToken: this.#sessionStorer.getApiToken(),
                taskText: filters.taskText,
                targetDateFrom: filters.targetDateFrom,
                targetDateTo: filters.targetDateTo,
                page,
                pageSize: TasksPaginationEnum.PAGE_SIZE,
            })
        );
    }

    #getSavedFilters() {
        try {
            const savedFilters = this.#localStorer.getItem(StorageKeyEnum.TASKS_FILTERS) ?? {};
            return {
                taskText: savedFilters.taskText ?? EMPTY_FILTERS.taskText,
                targetDateFrom: savedFilters.targetDateFrom ?? EMPTY_FILTERS.targetDateFrom,
                targetDateTo: savedFilters.targetDateTo ?? EMPTY_FILTERS.targetDateTo,
            };
        }
        catch (error) {
            console.error("ListTasksController.getSavedFilters", error);
            return { ...EMPTY_FILTERS };
        }
    }

    #saveFilters(filters) {
        try {
            this.#localStorer.setItem(StorageKeyEnum.TASKS_FILTERS, {
                taskText: filters.taskText,
                targetDateFrom: filters.targetDateFrom,
                targetDateTo: filters.targetDateTo,
            });
        }
        catch (error) {
            // sin localStorage los filtros valen para esta visita y no se recuerdan
            console.error("ListTasksController.saveFilters", error);
        }
    }
}
