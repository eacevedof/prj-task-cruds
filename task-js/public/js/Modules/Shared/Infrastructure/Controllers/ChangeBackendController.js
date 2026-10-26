import { BackendCodeEnum } from "/js/Modules/Shared/Domain/Enums/BackendCodeEnum.js";
import { SharedEventEnum } from "/js/Modules/Shared/Domain/Enums/SharedEventEnum.js";
import { StorageKeyEnum } from "/js/Modules/Shared/Domain/Enums/StorageKeyEnum.js";
import { GraphqlRequester } from "/js/Modules/Shared/Infrastructure/Components/GraphqlRequester.js";
import { LocalStorer } from "/js/Modules/Shared/Infrastructure/Components/LocalStorer.js";
import { SessionStorer } from "/js/Modules/Shared/Infrastructure/Components/SessionStorer.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";
import { NavigationView } from "/js/Modules/Shared/Infrastructure/Views/NavigationView.js";
import { RequestTimesView } from "/js/Modules/Shared/Infrastructure/Views/RequestTimesView.js";

/**
 * Back que consume la SPA (php, go...): preferencia del navegador (localStorage), no de la API, asi que no hay
 * caso de uso. Los backs los da /config.json; al arrancar se aplica el guardado (o el de por defecto).
 * Cambiar de back cierra la sesion (cada back tiene su BD: el api_token de uno no vale en otro) y recarga.
 */
export class ChangeBackendController {

    #localStorer;
    #sessionStorer;
    #subscriber;
    #graphqlRequester;
    #navigationView;
    #requestTimesView;

    constructor() {
        this.#localStorer = LocalStorer.getInstance();
        this.#sessionStorer = SessionStorer.getInstance();
        this.#subscriber = Subscriber.getInstance();
        this.#graphqlRequester = GraphqlRequester.getInstance();
        this.#navigationView = NavigationView.getInstance();
        this.#requestTimesView = RequestTimesView.getInstance();
    }

    /**
     * @returns {ChangeBackendController}
     */
    static getInstance() {
        return new ChangeBackendController();
    }

    init() {
        this.#subscriber.subscribe(SharedEventEnum.BACKEND_CHANGE_REQUESTED, (request) => this.invoke(request));
    }

    /**
     * Arranque, con la configuracion ya leida: la API de las llamadas es la del back guardado.
     * Un back guardado que ya no esta en la config cae en el de por defecto (o en el primero).
     * @param {{code: string, name: string, apiBaseUrl: string}[]} backends
     */
    applySavedBackend(backends) {
        const savedBackendCode = this.#getSavedBackendCode();
        const backend = backends.find((item) => item.code === savedBackendCode)
            ?? backends.find((item) => item.code === BackendCodeEnum.DEFAULT)
            ?? backends[0];
        this.#graphqlRequester.setApiBaseUrl(backend.apiBaseUrl);
        this.#navigationView.renderBackends(backends, backend.code);
        this.#requestTimesView.renderBackend(backend);
    }

    /**
     * @param {{backendCode: string}} request
     */
    invoke(request) {
        try {
            this.#localStorer.setItem(StorageKeyEnum.BACKEND_CODE, request.backendCode);
            this.#sessionStorer.removeSession();
        }
        catch (error) {
            // sin localStorage no se puede recordar el back: se queda en el actual
            console.error("ChangeBackendController.invoke", error);
        }
        this.#navigationView.reloadPage();
    }

    #getSavedBackendCode() {
        try {
            return this.#localStorer.getItem(StorageKeyEnum.BACKEND_CODE) ?? BackendCodeEnum.DEFAULT;
        }
        catch (error) {
            console.error("ChangeBackendController.getSavedBackendCode", error);
            return BackendCodeEnum.DEFAULT;
        }
    }
}
