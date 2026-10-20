/**
 * Entrada del caso de uso: solo primitivos, todo string con trim (la API tambien recorta la password).
 */
export class LogInDto {

    #userName;
    #password;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#userName = String(primitives.userName ?? "").trim();
        this.#password = String(primitives.password ?? "").trim();
    }

    /**
     * @param {Object} primitives
     * @returns {LogInDto}
     */
    static fromPrimitives(primitives = {}) {
        return new LogInDto(primitives);
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
    getPassword() {
        return this.#password;
    }
}
