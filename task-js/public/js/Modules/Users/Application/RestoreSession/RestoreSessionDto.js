/**
 * Entrada del caso de uso: solo primitivos, todo string con trim.
 */
export class RestoreSessionDto {

    #apiToken;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#apiToken = String(primitives.apiToken ?? "").trim();
    }

    /**
     * @param {Object} primitives
     * @returns {RestoreSessionDto}
     */
    static fromPrimitives(primitives = {}) {
        return new RestoreSessionDto(primitives);
    }

    /**
     * @returns {string}
     */
    getApiToken() {
        return this.#apiToken;
    }
}
