import { SharedEventEnum } from "/js/Modules/Shared/Domain/Enums/SharedEventEnum.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";
import { RequestProgressView } from "/js/Modules/Shared/Infrastructure/Views/RequestProgressView.js";

/**
 * Progreso de las llamadas a la API: el GraphqlRequester avisa al empezar y al terminar cada una, asi que
 * ningun caso de uso tiene que acordarse de abrir o cerrar el modal. No hay caso de uso: es solo interfaz.
 */
export class ShowRequestProgressController {

    #subscriber;
    #requestProgressView;

    constructor() {
        this.#subscriber = Subscriber.getInstance();
        this.#requestProgressView = RequestProgressView.getInstance();
    }

    /**
     * @returns {ShowRequestProgressController}
     */
    static getInstance() {
        return new ShowRequestProgressController();
    }

    init() {
        this.#subscriber.subscribe(SharedEventEnum.API_REQUEST_STARTED, () => this.#requestProgressView.addPendingRequest());
        this.#subscriber.subscribe(SharedEventEnum.API_REQUEST_FINISHED, () => this.#requestProgressView.removePendingRequest());
    }
}
