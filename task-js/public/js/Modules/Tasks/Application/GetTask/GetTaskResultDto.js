/**
 * Resultado del caso de uso: solo primitivos (un objeto o lista de objetos planos como los da la API).
 */
export class GetTaskResultDto {

    #task;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#task = primitives.task ?? {};
    }

    /**
     * @param {Object} primitives
     * @returns {GetTaskResultDto}
     */
    static fromPrimitives(primitives = {}) {
        return new GetTaskResultDto(primitives);
    }

    /**
     * @returns {Object}
     */
    getTask() {
        return this.#task;
    }
}
