/**
 * Resultado del caso de uso: solo primitivos (un objeto o lista de objetos planos como los da la API).
 */
export class DeleteAccountResultDto {

    #userUuid;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#userUuid = String(primitives.userUuid ?? "").trim();
    }

    /**
     * @param {Object} primitives
     * @returns {DeleteAccountResultDto}
     */
    static fromPrimitives(primitives = {}) {
        return new DeleteAccountResultDto(primitives);
    }

    /**
     * @returns {string}
     */
    getUserUuid() {
        return this.#userUuid;
    }
}
