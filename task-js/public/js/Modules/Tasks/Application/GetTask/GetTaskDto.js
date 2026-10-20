/**
 * Entrada del caso de uso: solo primitivos, todo string con trim.
 */
export class GetTaskDto {

    #apiToken;
    #taskUuid;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#apiToken = String(primitives.apiToken ?? "").trim();
        this.#taskUuid = String(primitives.taskUuid ?? "").trim();
    }

    /**
     * @param {Object} primitives
     * @returns {GetTaskDto}
     */
    static fromPrimitives(primitives = {}) {
        return new GetTaskDto(primitives);
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
    getTaskUuid() {
        return this.#taskUuid;
    }
}
