import { DateTimer } from "/js/Modules/Shared/Infrastructure/Components/DateTimer.js";
import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { Htmler } from "/js/Modules/Shared/Infrastructure/Components/Htmler.js";
import { Translator } from "/js/Modules/Shared/Infrastructure/Components/Translator/Translator.js";

import { TasksEventEnum } from "/js/Modules/Tasks/Domain/Enums/TasksEventEnum.js";
import { TasksFormEnum } from "/js/Modules/Tasks/Domain/Enums/TasksFormEnum.js";
import { TasksKeyEnum } from "/js/Modules/Tasks/Domain/Enums/TasksKeyEnum.js";
import { TasksPaginationEnum } from "/js/Modules/Tasks/Domain/Enums/TasksPaginationEnum.js";
import { TasksTextEnum } from "/js/Modules/Tasks/Domain/Enums/TasksTextEnum.js";
import { TaskStatusEnum } from "/js/Modules/Tasks/Domain/Enums/TaskStatusEnum.js";
import { TaskTypeEnum } from "/js/Modules/Tasks/Domain/Enums/TaskTypeEnum.js";

const CSS_HIDDEN = "hidden";
const CSS_ERROR = "message-error";
const ACTION_DETAIL = "detail";
const ACTION_EDIT = "edit";
const ACTION_DELETE = "delete";
// el buscador no pide la lista en cada tecla
const TEXT_FILTER_DEBOUNCE_MS = 300;
// en la lista solo un trozo de la descripcion; la entera en el tooltip (y en el detalle)
const DESCRIPTION_EXCERPT_CHARS = 90;

/**
 * Pantalla "Tareas": filtros, lista paginada (id desc), modal de detalle (solo lectura) y formulario
 * "Nueva tarea" (plegado; el mismo para editar, con task_uuid oculto).
 * Una sola instancia: guarda las referencias al DOM, las tareas pintadas y la paginacion.
 */
export class TasksView {

    static #instance = null;

    #dateTimer;
    #dispatcher;
    #htmler;
    #translator;
    #elements = {};
    #tasksByUuid = new Map();
    #pagination = { page: TasksPaginationEnum.FIRST_PAGE, totalPages: 0, totalItems: 0 };
    #textFilterTimeoutId = null;
    // task_id de la tarea que se edita (va en el titulo del formulario); "" = alta
    #editingTaskId = "";

    constructor() {
        this.#dateTimer = DateTimer.getInstance();
        this.#dispatcher = Dispatcher.getInstance();
        this.#htmler = Htmler.getInstance();
        this.#translator = Translator.getInstance();
    }

    /**
     * @returns {TasksView}
     */
    static getInstance() {
        TasksView.#instance ??= new TasksView();
        return TasksView.#instance;
    }

    init() {
        this.#loadElements();
        this.#loadSelectOptions();
        this.#elements.detailsTaskForm.addEventListener("toggle", () => this.#onTaskFormToggle());
        this.#elements.formTask.addEventListener("submit", (event) => this.#onTaskSubmit(event));
        this.#elements.formTask.addEventListener("keydown", (event) => this.#onTaskFormKeydown(event));
        this.#elements.buttonCancelTask.addEventListener("click", () => this.resetForm());
        this.#elements.tbodyTasks.addEventListener("click", (event) => this.#onTasksTableClick(event));
        this.#elements.formFilters.addEventListener("submit", (event) => this.#onFiltersSubmit(event));
        this.#elements.inputFilterTaskText.addEventListener("input", () => this.#onTextFilterInput());
        this.#elements.inputFilterTargetDateFrom.addEventListener("change", () => this.#dispatchListTasks(TasksPaginationEnum.FIRST_PAGE));
        this.#elements.inputFilterTargetDateTo.addEventListener("change", () => this.#dispatchListTasks(TasksPaginationEnum.FIRST_PAGE));
        this.#elements.buttonClearFilters.addEventListener("click", () => this.#onClearFiltersClick());
        this.#elements.buttonPreviousPage.addEventListener("click", () => this.#dispatchListTasks(this.#pagination.page - 1));
        this.#elements.buttonNextPage.addEventListener("click", () => this.#dispatchListTasks(this.#pagination.page + 1));
        this.#elements.buttonCloseTaskDetail.addEventListener("click", () => this.#closeTaskDetail());
    }

    /**
     * Al entrar o salir: formulario plegado y vacio, sin avisos, tareas ni modal.
     */
    reset() {
        this.resetForm();
        this.#renderMessage("", false);
        this.#closeTaskDetail();
        this.renderTasks([]);
        this.renderPagination({ page: TasksPaginationEnum.FIRST_PAGE, totalPages: 0, totalItems: 0 });
    }

    resetForm() {
        this.#elements.formTask.reset();
        this.#elements.hiddenTaskUuid.value = "";
        this.#editingTaskId = "";
        this.#elements.inputTargetDate.value = this.#dateTimer.getDateFromToday(TasksFormEnum.TARGET_DATE_DAYS_FROM_TODAY);
        this.#renderFormTitle();
        this.#elements.buttonCancelTask.classList.add(CSS_HIDDEN);
        this.#elements.detailsTaskForm.open = false;
        this.renderBusy(false);
    }

    /**
     * @param {boolean} isBusy
     */
    renderBusy(isBusy) {
        this.#elements.sectionTasks.querySelectorAll("button").forEach((button) => button.disabled = isBusy);
        if (!isBusy) this.#renderPaginationButtons();
    }

    /**
     * @param {{taskText: string, targetDateFrom: string, targetDateTo: string}} filters
     */
    renderFilters(filters) {
        this.#elements.inputFilterTaskText.value = filters.taskText;
        this.#elements.inputFilterTargetDateFrom.value = filters.targetDateFrom;
        this.#elements.inputFilterTargetDateTo.value = filters.targetDateTo;
    }

    /**
     * @param {Object[]} tasks la pagina actual, tal cual la devuelve la API (claves snake_case)
     */
    renderTasks(tasks) {
        this.#tasksByUuid = new Map(tasks.map((task) => [task[TasksKeyEnum.TASK_UUID], task]));
        this.#renderTasksTable();
    }

    /**
     * @param {{page: number, totalPages: number, totalItems: number}} pagination
     */
    renderPagination(pagination) {
        this.#pagination = pagination;
        this.#renderPaginationBar();
    }

    /**
     * Modal de solo lectura con todo el detalle de la tarea.
     * @param {Object} task tal cual la devuelve la API
     */
    renderTaskDetail(task) {
        const emptyValue = this.#translator.getText(TasksTextEnum.EMPTY_VALUE);
        this.#elements.h2TaskDetail.textContent = this.#translator.getText(TasksTextEnum.DETAIL_TITLE, { taskId: task[TasksKeyEnum.TASK_ID] });
        this.#elements.taskDetailFields.forEach((field) => {
            const key = field.dataset.field;
            const value = task[key];
            field.textContent = this.#getFieldLabel(key, value) || emptyValue;
        });
        this.#renderMessage("", false);
        // <dialog>: modal nativo (foco atrapado, Esc cierra); sin soporte, al menos visible
        if (typeof this.#elements.dialogTaskDetail.showModal === "function") {
            this.#elements.dialogTaskDetail.showModal();
            return;
        }
        this.#elements.dialogTaskDetail.setAttribute("open", "");
    }

    /**
     * El idioma cambio: lo que pinta el js (selects, tabla, paginacion, titulo del formulario).
     */
    renderTranslations() {
        this.#loadSelectOptions();
        this.#renderTasksTable();
        this.#renderPaginationBar();
        this.#renderFormTitle();
    }

    /**
     * @param {string} textKey TasksTextEnum
     */
    renderSuccess(textKey) {
        this.resetForm();
        this.#renderMessage(this.#translator.getText(textKey), false);
    }

    /**
     * @param {string[]} errors
     */
    renderErrors(errors) {
        this.renderBusy(false);
        this.#renderMessage(errors.join("\n"), true);
    }

    #loadElements() {
        this.#elements = {
            sectionTasks: document.getElementById("section-tasks"),
            divTasksMessage: document.getElementById("div-tasks-message"),
            detailsTaskForm: document.getElementById("details-task-form"),
            formTask: document.getElementById("form-task"),
            h2TaskForm: document.getElementById("h2-task-form"),
            hiddenTaskUuid: document.getElementById("hidden-task-uuid"),
            inputTaskTitle: document.getElementById("input-task-title"),
            selectTaskStatus: document.getElementById("select-task-status"),
            selectTaskType: document.getElementById("select-task-type"),
            inputTargetDate: document.getElementById("input-target-date"),
            textareaTaskDescription: document.getElementById("textarea-task-description"),
            buttonCancelTask: document.getElementById("button-cancel-task"),
            formFilters: document.getElementById("form-filters"),
            inputFilterTaskText: document.getElementById("input-filter-task-text"),
            inputFilterTargetDateFrom: document.getElementById("input-filter-target-date-from"),
            inputFilterTargetDateTo: document.getElementById("input-filter-target-date-to"),
            buttonClearFilters: document.getElementById("button-clear-filters"),
            tableTasks: document.getElementById("table-tasks"),
            tbodyTasks: document.getElementById("tbody-tasks"),
            spanTasksCount: document.getElementById("span-tasks-count"),
            pTasksEmpty: document.getElementById("p-tasks-empty"),
            divPagination: document.getElementById("div-pagination"),
            spanPageOf: document.getElementById("span-page-of"),
            buttonPreviousPage: document.getElementById("button-previous-page"),
            buttonNextPage: document.getElementById("button-next-page"),
            dialogTaskDetail: document.getElementById("dialog-task-detail"),
            h2TaskDetail: document.getElementById("h2-task-detail"),
            taskDetailFields: [...document.querySelectorAll("#dialog-task-detail [data-field]")],
            buttonCloseTaskDetail: document.getElementById("button-close-task-detail"),
        };
    }

    /**
     * Los valores salen del enum (los mismos de la API) y la etiqueta del Translator; se conserva lo elegido.
     */
    #loadSelectOptions() {
        const selectTaskStatus = this.#elements.selectTaskStatus;
        const selectTaskType = this.#elements.selectTaskType;
        const taskStatus = selectTaskStatus.value || TaskStatusEnum.PENDING;
        const taskType = selectTaskType.value || TaskTypeEnum.PERSONAL;
        selectTaskStatus.innerHTML = this.#getHtmlOptions(TaskStatusEnum.VALUES, TasksTextEnum.STATUS_PREFIX);
        selectTaskType.innerHTML = this.#getHtmlOptions(TaskTypeEnum.VALUES, TasksTextEnum.TYPE_PREFIX);
        selectTaskStatus.value = taskStatus;
        selectTaskType.value = taskType;
    }

    #getHtmlOptions(values, textKeyPrefix) {
        return values
            .map((value) => `<option value="${this.#htmler.getEscaped(value)}">${this.#htmler.getEscaped(this.#translator.getText(`${textKeyPrefix}${value}`))}</option>`)
            .join("");
    }

    /**
     * Al desplegar "Nueva tarea", directo a escribir el titulo (al editar ya lo pone #loadFormWithTask).
     */
    #onTaskFormToggle() {
        if (!this.#elements.detailsTaskForm.open) return;
        this.#elements.inputTaskTitle.focus();
    }

    /**
     * Ctrl+Enter (Cmd+Enter en Mac) guarda desde cualquier campo, tambien desde la descripcion.
     * requestSubmit y no submit(): pasa por la validacion del formulario y por #onTaskSubmit.
     */
    #onTaskFormKeydown(event) {
        if (event.key !== "Enter" || !(event.ctrlKey || event.metaKey)) return;

        event.preventDefault();
        this.#elements.formTask.requestSubmit();
    }

    #onTaskSubmit(event) {
        event.preventDefault();
        const taskUuid = this.#elements.hiddenTaskUuid.value;
        const task = {
            taskTitle: this.#elements.inputTaskTitle.value,
            taskStatus: this.#elements.selectTaskStatus.value,
            taskType: this.#elements.selectTaskType.value,
            targetDate: this.#elements.inputTargetDate.value,
            taskDescription: this.#elements.textareaTaskDescription.value,
        };
        this.renderBusy(true);
        if (!taskUuid) {
            this.#dispatcher.dispatch(TasksEventEnum.CREATE_TASK_REQUESTED, task);
            return;
        }
        this.#dispatcher.dispatch(TasksEventEnum.UPDATE_TASK_REQUESTED, { taskUuid, ...task });
    }

    #onTasksTableClick(event) {
        const actionElement = event.target.closest("[data-action]");
        if (!actionElement) return;

        event.preventDefault();
        const task = this.#tasksByUuid.get(actionElement.dataset.taskUuid);
        if (!task) return;

        if (actionElement.dataset.action === ACTION_DETAIL) {
            this.#dispatcher.dispatch(TasksEventEnum.TASK_DETAIL_REQUESTED, { taskUuid: task[TasksKeyEnum.TASK_UUID] });
            return;
        }
        if (actionElement.dataset.action === ACTION_EDIT) {
            this.#loadFormWithTask(task);
            return;
        }
        const confirmMessage = this.#translator.getText(TasksTextEnum.CONFIRM_DELETE, { title: task[TasksKeyEnum.TASK_TITLE] });
        if (!window.confirm(confirmMessage)) return;

        this.renderBusy(true);
        this.#dispatcher.dispatch(TasksEventEnum.DELETE_TASK_REQUESTED, { taskUuid: task[TasksKeyEnum.TASK_UUID] });
    }

    #loadFormWithTask(task) {
        this.#elements.hiddenTaskUuid.value = task[TasksKeyEnum.TASK_UUID];
        this.#editingTaskId = String(task[TasksKeyEnum.TASK_ID] ?? "");
        this.#elements.inputTaskTitle.value = task[TasksKeyEnum.TASK_TITLE] ?? "";
        this.#elements.selectTaskStatus.value = task[TasksKeyEnum.TASK_STATUS] ?? TaskStatusEnum.PENDING;
        this.#elements.selectTaskType.value = task[TasksKeyEnum.TASK_TYPE] ?? TaskTypeEnum.PERSONAL;
        this.#elements.inputTargetDate.value = task[TasksKeyEnum.TARGET_DATE] ?? "";
        this.#elements.textareaTaskDescription.value = task[TasksKeyEnum.TASK_DESCRIPTION] ?? "";
        this.#renderFormTitle();
        this.#elements.buttonCancelTask.classList.remove(CSS_HIDDEN);
        this.#elements.detailsTaskForm.open = true;
        this.#renderMessage("", false);
        this.#elements.inputTaskTitle.focus();
    }

    /**
     * "New task" en el alta; "Edit task #12" al editar, con el task_id como en el modal de detalle.
     */
    #renderFormTitle() {
        this.#elements.h2TaskForm.textContent = this.#editingTaskId
            ? this.#translator.getText(TasksTextEnum.FORM_TITLE_UPDATE, { taskId: this.#editingTaskId })
            : this.#translator.getText(TasksTextEnum.FORM_TITLE_CREATE);
    }

    /**
     * Enter en un filtro o la tool search_tasks de WebMCP (la IA rellena los campos y envia sin pasar por
     * input/change): busca ya, sin esperar al debounce del texto.
     */
    #onFiltersSubmit(event) {
        event.preventDefault();
        clearTimeout(this.#textFilterTimeoutId);
        this.#dispatchListTasks(TasksPaginationEnum.FIRST_PAGE);
    }

    #onTextFilterInput() {
        clearTimeout(this.#textFilterTimeoutId);
        this.#textFilterTimeoutId = setTimeout(() => this.#dispatchListTasks(TasksPaginationEnum.FIRST_PAGE), TEXT_FILTER_DEBOUNCE_MS);
    }

    #onClearFiltersClick() {
        this.renderFilters({
            taskText: "",
            targetDateFrom: "",
            targetDateTo: "",
        });
        this.#dispatchListTasks(TasksPaginationEnum.FIRST_PAGE);
    }

    /**
     * @param {number} page la que se pide (cambiar un filtro siempre vuelve a la primera)
     */
    #dispatchListTasks(page) {
        this.#renderMessage("", false);
        this.#dispatcher.dispatch(TasksEventEnum.LIST_TASKS_REQUESTED, {
            filters: this.#getFilters(),
            page,
        });
    }

    #getFilters() {
        return {
            taskText: this.#elements.inputFilterTaskText.value,
            targetDateFrom: this.#elements.inputFilterTargetDateFrom.value,
            targetDateTo: this.#elements.inputFilterTargetDateTo.value,
        };
    }

    #closeTaskDetail() {
        if (typeof this.#elements.dialogTaskDetail.close === "function") {
            this.#elements.dialogTaskDetail.close();
            return;
        }
        this.#elements.dialogTaskDetail.removeAttribute("open");
    }

    #renderTasksTable() {
        const tasks = [...this.#tasksByUuid.values()];
        this.#elements.tbodyTasks.innerHTML = tasks.map((task) => this.#getHtmlTaskRow(task)).join("");
        this.#elements.tableTasks.classList.toggle(CSS_HIDDEN, tasks.length === 0);
    }

    #renderPaginationBar() {
        const { page, totalPages, totalItems } = this.#pagination;
        const filters = this.#getFilters();
        const hasFilters = Boolean(filters.taskText || filters.targetDateFrom || filters.targetDateTo);

        this.#elements.spanTasksCount.textContent = totalItems ? `(${totalItems})` : "";
        this.#elements.pTasksEmpty.classList.toggle(CSS_HIDDEN, totalItems > 0);
        this.#elements.pTasksEmpty.textContent = this.#translator.getText(hasFilters ? TasksTextEnum.LIST_NO_MATCHES : TasksTextEnum.LIST_EMPTY);
        this.#elements.divPagination.classList.toggle(CSS_HIDDEN, totalItems === 0);
        this.#elements.spanPageOf.textContent = this.#translator.getText(TasksTextEnum.PAGE_OF, { page, totalPages, totalItems });
        this.#renderPaginationButtons();
    }

    /**
     * Sin pagina anterior o siguiente, su boton no se ve (no solo deshabilitado).
     */
    #renderPaginationButtons() {
        const hasPreviousPage = this.#pagination.page > TasksPaginationEnum.FIRST_PAGE;
        const hasNextPage = this.#pagination.page < this.#pagination.totalPages;
        this.#elements.buttonPreviousPage.classList.toggle(CSS_HIDDEN, !hasPreviousPage);
        this.#elements.buttonNextPage.classList.toggle(CSS_HIDDEN, !hasNextPage);
        this.#elements.buttonPreviousPage.disabled = !hasPreviousPage;
        this.#elements.buttonNextPage.disabled = !hasNextPage;
    }

    /**
     * Descripcion en una linea; si es larga se corta con "…" y la entera va en el tooltip (title).
     * @param {string|null} taskDescription
     * @returns {string}
     */
    #getHtmlTaskDescription(taskDescription) {
        const fullDescription = String(taskDescription ?? "").trim();
        const oneLineDescription = fullDescription.replace(/\s+/g, " ");
        if (oneLineDescription.length <= DESCRIPTION_EXCERPT_CHARS) {
            return `<div class="task-description">${this.#htmler.getEscaped(oneLineDescription)}</div>`;
        }
        const excerpt = `${oneLineDescription.slice(0, DESCRIPTION_EXCERPT_CHARS).trimEnd()}…`;
        return `<div class="task-description task-description-excerpt" title="${this.#htmler.getEscaped(fullDescription)}" tabindex="0">${this.#htmler.getEscaped(excerpt)}</div>`;
    }

    #getHtmlTaskRow(task) {
        const taskUuid = this.#htmler.getEscaped(task[TasksKeyEnum.TASK_UUID]);
        const taskStatus = task[TasksKeyEnum.TASK_STATUS];
        return `
<tr>
    <td class="task-id">${this.#htmler.getEscaped(task[TasksKeyEnum.TASK_ID])}</td>
    <td>
        <a href="#tasks" class="task-title" data-action="${ACTION_DETAIL}" data-task-uuid="${taskUuid}">${this.#htmler.getEscaped(task[TasksKeyEnum.TASK_TITLE])}</a>
        ${this.#getHtmlTaskDescription(task[TasksKeyEnum.TASK_DESCRIPTION])}
    </td>
    <td><span class="badge badge-${this.#htmler.getEscaped(taskStatus)}">${this.#htmler.getEscaped(this.#getFieldLabel(TasksKeyEnum.TASK_STATUS, taskStatus))}</span></td>
    <td>${this.#htmler.getEscaped(this.#getFieldLabel(TasksKeyEnum.TASK_TYPE, task[TasksKeyEnum.TASK_TYPE]))}</td>
    <td>${this.#htmler.getEscaped(task[TasksKeyEnum.TARGET_DATE])}</td>
    <td class="actions">
        <button type="button" data-action="${ACTION_EDIT}" data-task-uuid="${taskUuid}">${this.#htmler.getEscaped(this.#translator.getText(TasksTextEnum.EDIT))}</button>
        <button type="button" class="danger" data-action="${ACTION_DELETE}" data-task-uuid="${taskUuid}">${this.#htmler.getEscaped(this.#translator.getText(TasksTextEnum.DELETE))}</button>
    </td>
</tr>`;
    }

    /**
     * Estado y tipo se ven con su etiqueta traducida; el resto, tal cual.
     */
    #getFieldLabel(key, value) {
        if (value === null || value === undefined || value === "") return "";
        if (key === TasksKeyEnum.TASK_STATUS) return this.#translator.getText(`${TasksTextEnum.STATUS_PREFIX}${value}`);
        if (key === TasksKeyEnum.TASK_TYPE) return this.#translator.getText(`${TasksTextEnum.TYPE_PREFIX}${value}`);
        return String(value);
    }

    #renderMessage(message, isError) {
        this.#elements.divTasksMessage.innerHTML = this.#htmler.getEscaped(message).replaceAll("\n", "<br>");
        this.#elements.divTasksMessage.classList.toggle(CSS_HIDDEN, !message);
        this.#elements.divTasksMessage.classList.toggle(CSS_ERROR, isError);
    }
}
