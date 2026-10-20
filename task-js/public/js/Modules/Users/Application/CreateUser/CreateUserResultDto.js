/**
 * Resultado del caso de uso: solo primitivos (un objeto o lista de objetos planos como los da la API).
 */
export class CreateUserResultDto {

    #apiToken;
    #userUuid;
    #userName;
    #userEmail;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#apiToken = String(primitives.apiToken ?? "").trim();
        this.#userUuid = String(primitives.userUuid ?? "").trim();
        this.#userName = String(primitives.userName ?? "").trim();
        this.#userEmail = String(primitives.userEmail ?? "").trim();
    }

    /**
     * @param {Object} primitives
     * @returns {CreateUserResultDto}
     */
    static fromPrimitives(primitives = {}) {
        return new CreateUserResultDto(primitives);
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
