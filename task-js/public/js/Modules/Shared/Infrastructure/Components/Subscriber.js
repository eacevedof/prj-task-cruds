export class Subscriber {

    /**
     * @returns {Subscriber}
     */
    static getInstance() {
        return new Subscriber();
    }

    /**
     * @param {string} eventName
     * @param {function(Object): (void|Promise<void>)} onEvent recibe los primitivos que envio el Dispatcher
     */
    subscribe(eventName, onEvent) {
        document.addEventListener(eventName, (event) => onEvent(event.detail?.request ?? {}));
    }
}
