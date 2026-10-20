/**
 * Resultado del caso de uso: solo primitivos (un objeto o lista de objetos planos como los da la API).
 */
export class DeleteTaskResultDto {

    #taskUuid;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#taskUuid = String(primitives.taskUuid ?? "").trim();
    }

    /**
     * @param {Object} primitives
     * @returns {DeleteTaskResultDto}
     */
    static fromPrimitives(primitives = {}) {
        return new DeleteTaskResultDto(primitives);
    }

    /**
     * @returns {string}
     */
    getTaskUuid() {
        return this.#taskUuid;
    }
}
