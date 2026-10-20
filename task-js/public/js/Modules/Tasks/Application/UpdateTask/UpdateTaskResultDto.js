/**
 * Resultado del caso de uso: solo primitivos (un objeto o lista de objetos planos como los da la API).
 */
export class UpdateTaskResultDto {

    #task;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#task = primitives.task ?? {};
    }

    /**
     * @param {Object} primitives
     * @returns {UpdateTaskResultDto}
     */
    static fromPrimitives(primitives = {}) {
        return new UpdateTaskResultDto(primitives);
    }

    /**
     * @returns {Object}
     */
    getTask() {
        return this.#task;
    }
}
