import { BackendCodeEnum } from "/js/Modules/Shared/Domain/Enums/BackendCodeEnum.js";
import { StorageKeyEnum } from "/js/Modules/Shared/Domain/Enums/StorageKeyEnum.js";
import { GraphqlRequester } from "/js/Modules/Shared/Infrastructure/Components/GraphqlRequester.js";
import { LocalStorer } from "/js/Modules/Shared/Infrastructure/Components/LocalStorer.js";
import { NavigationView } from "/js/Modules/Shared/Infrastructure/Views/NavigationView.js";
import { RequestTimesView } from "/js/Modules/Shared/Infrastructure/Views/RequestTimesView.js";

/**
 * Arranque, con la configuracion ya leida (/config.json): la API de las llamadas es la del back guardado en el
 * navegador (localStorage). Un back guardado que ya no esta en la config cae en el de por defecto (o en el primero).
 * Cambiarlo es cosa de ChangeBackendController.
 */
export class ApplySavedBackendController {

    #localStorer;
    #graphqlRequester;
    #navigationView;
    #requestTimesView;

    constructor() {
        this.#localStorer = LocalStorer.getInstance();
        this.#graphqlRequester = GraphqlRequester.getInstance();
        this.#navigationView = NavigationView.getInstance();
        this.#requestTimesView = RequestTimesView.getInstance();
    }

    /**
     * @returns {ApplySavedBackendController}
     */
    static getInstance() {
        return new ApplySavedBackendController();
    }

    /**
     * @param {{code: string, name: string, apiBaseUrl: string}[]} backends
     */
    invoke(backends) {
        const savedBackendCode = this.#getSavedBackendCode();
        const backend = backends.find((item) => item.code === savedBackendCode)
            ?? backends.find((item) => item.code === BackendCodeEnum.DEFAULT)
            ?? backends[0];
        this.#graphqlRequester.setApiBaseUrl(backend.apiBaseUrl);
        this.#navigationView.renderBackends(backends, backend.code);
        this.#requestTimesView.renderBackend(backend);
    }

    #getSavedBackendCode() {
        try {
            return this.#localStorer.getItem(StorageKeyEnum.BACKEND_CODE) ?? BackendCodeEnum.DEFAULT;
        }
        catch (error) {
            console.error("ApplySavedBackendController.getSavedBackendCode", error);
            return BackendCodeEnum.DEFAULT;
        }
    }
}
