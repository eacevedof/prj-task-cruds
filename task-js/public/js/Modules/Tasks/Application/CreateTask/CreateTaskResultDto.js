/**
 * Resultado del caso de uso: solo primitivos (un objeto o lista de objetos planos como los da la API).
 */
export class CreateTaskResultDto {

    #task;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#task = primitives.task ?? {};
    }

    /**
     * @param {Object} primitives
     * @returns {CreateTaskResultDto}
     */
    static fromPrimitives(primitives = {}) {
        return new CreateTaskResultDto(primitives);
    }

    /**
     * @returns {Object}
     */
    getTask() {
        return this.#task;
    }
}
