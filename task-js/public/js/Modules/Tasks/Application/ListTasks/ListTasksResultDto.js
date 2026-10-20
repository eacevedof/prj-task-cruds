/**
 * Resultado del caso de uso: una pagina de tareas (objetos planos como los da la API) y su paginacion.
 */
export class ListTasksResultDto {

    #tasks;
    #page;
    #pageSize;
    #totalItems;
    #totalPages;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#tasks = Array.isArray(primitives.tasks) ? primitives.tasks : [];
        this.#page = Number(primitives.page ?? 0);
        this.#pageSize = Number(primitives.pageSize ?? 0);
        this.#totalItems = Number(primitives.totalItems ?? 0);
        this.#totalPages = Number(primitives.totalPages ?? 0);
    }

    /**
     * @param {Object} primitives
     * @returns {ListTasksResultDto}
     */
    static fromPrimitives(primitives = {}) {
        return new ListTasksResultDto(primitives);
    }

    /**
     * @returns {Object[]} las de la pagina pedida (id desc)
     */
    getTasks() {
        return this.#tasks;
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

    /**
     * @returns {number} las que cumplen los filtros, en todas las paginas
     */
    getTotalItems() {
        return this.#totalItems;
    }

    /**
     * @returns {number}
     */
    getTotalPages() {
        return this.#totalPages;
    }
}
