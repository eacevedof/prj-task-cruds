/**
 * Entrada del caso de uso: solo primitivos, todo string con trim.
 */
export class UpdateAccountDto {

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
     * @returns {UpdateAccountDto}
     */
    static fromPrimitives(primitives = {}) {
        return new UpdateAccountDto(primitives);
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
