/**
 * Bus de eventos de la SPA sobre document: la vista dispara, el controller (Subscriber) escucha.
 */
export class Dispatcher {

    /**
     * @returns {Dispatcher}
     */
    static getInstance() {
        return new Dispatcher();
    }

    /**
     * @param {string} eventName
     * @param {Object} request primitivos que recibira el controller
     */
    dispatch(eventName, request = {}) {
        document.dispatchEvent(new CustomEvent(eventName, { detail: { request } }));
    }
}
