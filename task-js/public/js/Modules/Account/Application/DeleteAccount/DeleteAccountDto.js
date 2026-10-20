/**
 * Entrada del caso de uso: solo primitivos, todo string con trim.
 */
export class DeleteAccountDto {

    #apiToken;
    #userUuid;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#apiToken = String(primitives.apiToken ?? "").trim();
        this.#userUuid = String(primitives.userUuid ?? "").trim();
    }

    /**
     * @param {Object} primitives
     * @returns {DeleteAccountDto}
     */
    static fromPrimitives(primitives = {}) {
        return new DeleteAccountDto(primitives);
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
}
