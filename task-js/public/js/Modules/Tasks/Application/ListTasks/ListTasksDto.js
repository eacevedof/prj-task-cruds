import { TasksPaginationEnum } from "/js/Modules/Tasks/Domain/Enums/TasksPaginationEnum.js";

/**
 * Entrada del caso de uso: solo primitivos, todo string con trim.
 */
export class ListTasksDto {

    #apiToken;
    #taskText;
    #targetDateFrom;
    #targetDateTo;
    #page;
    #pageSize;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#apiToken = String(primitives.apiToken ?? "").trim();
        this.#taskText = String(primitives.taskText ?? "").trim();
        this.#targetDateFrom = String(primitives.targetDateFrom ?? "").trim();
        this.#targetDateTo = String(primitives.targetDateTo ?? "").trim();
        this.#page = Number(primitives.page) || TasksPaginationEnum.FIRST_PAGE;
        this.#pageSize = Number(primitives.pageSize) || TasksPaginationEnum.PAGE_SIZE;
    }

    /**
     * @param {Object} primitives
     * @returns {ListTasksDto}
     */
    static fromPrimitives(primitives = {}) {
        return new ListTasksDto(primitives);
    }

    /**
     * @returns {string}
     */
    getApiToken() {
        return this.#apiToken;
    }

    /**
     * @returns {string}
     */
    getTaskText() {
        return this.#taskText;
    }

    /**
     * @returns {string}
     */
    getTargetDateFrom() {
        return this.#targetDateFrom;
    }

    /**
     * @returns {string}
     */
    getTargetDateTo() {
        return this.#targetDateTo;
    }

    /**
     * @returns {number}
     */
    getPage() {
        return this.#page;
    }

    /**
     * @returns {number}
     */
    getPageSize() {
        return this.#pageSize;
    }
}
