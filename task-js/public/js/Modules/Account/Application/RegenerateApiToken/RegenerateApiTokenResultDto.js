/**
 * Resultado del caso de uso: solo primitivos (un objeto o lista de objetos planos como los da la API).
 */
export class RegenerateApiTokenResultDto {

    #userUuid;
    #apiToken;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#userUuid = String(primitives.userUuid ?? "").trim();
        this.#apiToken = String(primitives.apiToken ?? "").trim();
    }

    /**
     * @param {Object} primitives
     * @returns {RegenerateApiTokenResultDto}
     */
    static fromPrimitives(primitives = {}) {
        return new RegenerateApiTokenResultDto(primitives);
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
    getApiToken() {
        return this.#apiToken;
    }
}
