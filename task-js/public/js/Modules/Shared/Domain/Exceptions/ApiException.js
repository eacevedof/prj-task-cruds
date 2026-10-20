/**
 * Base de las excepciones de dominio de cada modulo: un fallo controlado con su codigo HTTP y el detalle
 * por campo (errors) tal como lo devuelve la API.
 */
export class ApiException extends Error {

    #code;
    #errors;

    /**
     * @param {string} message
     * @param {number} code
     * @param {string[]} errors
     */
    constructor(message, code, errors = []) {
        super(message);
        this.name = this.constructor.name;
        this.#code = code;
        this.#errors = errors.length ? errors : [message];
    }

    /**
     * @returns {number}
     */
    getCode() {
        return this.#code;
    }

    /**
     * @returns {string[]}
     */
    getErrors() {
        return this.#errors;
    }
}
