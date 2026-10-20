/**
 * Resultado del caso de uso: solo primitivos (un objeto o lista de objetos planos como los da la API).
 */
export class UpdateAccountResultDto {

    #userUuid;
    #userName;
    #userEmail;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#userUuid = String(primitives.userUuid ?? "").trim();
        this.#userName = String(primitives.userName ?? "").trim();
        this.#userEmail = String(primitives.userEmail ?? "").trim();
    }

    /**
     * @param {Object} primitives
     * @returns {UpdateAccountResultDto}
     */
    static fromPrimitives(primitives = {}) {
        return new UpdateAccountResultDto(primitives);
    }

    /**
     * @returns {string}
     */
    getUserUuid() {
        return this.#userUuid;
    }

    /**
     * @returns {string}
     */
    getUserName() {
        return this.#userName;
    }

    /**
     * @returns {string}
     */
    getUserEmail() {
        return this.#userEmail;
    }
}
