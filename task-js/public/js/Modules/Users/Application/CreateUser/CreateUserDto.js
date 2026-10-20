/**
 * Entrada del caso de uso: solo primitivos, todo string con trim.
 */
export class CreateUserDto {

    #userName;
    #userEmail;
    #password;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#userName = String(primitives.userName ?? "").trim();
        this.#userEmail = String(primitives.userEmail ?? "").trim();
        this.#password = String(primitives.password ?? "").trim();
    }

    /**
     * @param {Object} primitives
     * @returns {CreateUserDto}
     */
    static fromPrimitives(primitives = {}) {
        return new CreateUserDto(primitives);
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

    /**
     * @returns {string}
     */
    getPassword() {
        return this.#password;
    }
}
