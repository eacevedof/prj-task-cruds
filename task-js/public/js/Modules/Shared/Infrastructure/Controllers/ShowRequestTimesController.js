import { SharedEventEnum } from "/js/Modules/Shared/Domain/Enums/SharedEventEnum.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";
import { RequestTimesView } from "/js/Modules/Shared/Infrastructure/Views/RequestTimesView.js";

/**
 * Tiempo de respuesta de cada llamada a la API en el footer: el GraphqlRequester lo mide y lo manda al
 * terminar. No hay caso de uso: es solo interfaz y no se guarda (se pierde al recargar).
 */
export class ShowRequestTimesController {

    #subscriber;
    #requestTimesView;

    constructor() {
        this.#subscriber = Subscriber.getInstance();
        this.#requestTimesView = RequestTimesView.getInstance();
    }

    /**
     * @returns {ShowRequestTimesController}
     */
    static getInstance() {
        return new ShowRequestTimesController();
    }

    init() {
        this.#subscriber.subscribe(SharedEventEnum.API_REQUEST_FINISHED, (request) => this.invoke(request));
    }

    /**
     * @param {{operationName: string, durationMs: number, isOk: boolean}} request
     */
    invoke(request) {
        this.#requestTimesView.addRequestTime({
            operationName: request.operationName,
            durationMs: Math.round(request.durationMs),
            isOk: request.isOk,
            finishedAt: new Date(),
        });
    }
}
