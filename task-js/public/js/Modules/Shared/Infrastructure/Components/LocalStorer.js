/**
 * Preferencias del navegador que sobreviven a recargar (idioma, filtros) en localStorage, como JSON.
 * Como todo componente no captura: si el navegador bloquea localStorage el error sube a quien lo usa.
 */
export class LocalStorer {

    /**
     * @returns {LocalStorer}
     */
    static getInstance() {
        return new LocalStorer();
    }

    /**
     * @param {string} key
     * @returns {*} lo guardado, o null si no hay nada
     */
    getItem(key) {
        return JSON.parse(localStorage.getItem(key) ?? "null");
    }

    /**
     * @param {string} key
     * @param {*} value
     */
    setItem(key, value) {
        localStorage.setItem(key, JSON.stringify(value));
    }

    /**
     * @param {string} key
     */
    removeItem(key) {
        localStorage.removeItem(key);
    }
}
