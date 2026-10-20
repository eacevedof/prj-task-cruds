const HTML_ENTITIES = Object.freeze({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    "\"": "&quot;",
    "'": "&#39;",
});

/**
 * Todo texto que viene de la API o del usuario se escapa antes de entrar en innerHTML (XSS).
 */
export class Htmler {

    /**
     * @returns {Htmler}
     */
    static getInstance() {
        return new Htmler();
    }

    /**
     * @param {*} text
     * @returns {string}
     */
    getEscaped(text) {
        return String(text ?? "").replace(/[&<>"']/g, (char) => HTML_ENTITIES[char]);
    }
}
