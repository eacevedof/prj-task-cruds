import { SharedEventEnum } from "/js/Modules/Shared/Domain/Enums/SharedEventEnum.js";
import { StorageKeyEnum } from "/js/Modules/Shared/Domain/Enums/StorageKeyEnum.js";
import { LocalStorer } from "/js/Modules/Shared/Infrastructure/Components/LocalStorer.js";
import { SessionStorer } from "/js/Modules/Shared/Infrastructure/Components/SessionStorer.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";
import { NavigationView } from "/js/Modules/Shared/Infrastructure/Views/NavigationView.js";

/**
 * Back que consume la SPA (php, go...): preferencia del navegador (localStorage), no de la API, asi que no hay
 * caso de uso. Al arrancar lo aplica ApplySavedBackendController.
 * Cambiar de back cierra la sesion (cada back tiene su BD: el api_token de uno no vale en otro) y recarga.
 */
export class ChangeBackendController {

    #localStorer;
    #sessionStorer;
    #subscriber;
    #navigationView;

    constructor() {
        this.#localStorer = LocalStorer.getInstance();
        this.#sessionStorer = SessionStorer.getInstance();
        this.#subscriber = Subscriber.getInstance();
        this.#navigationView = NavigationView.getInstance();
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
}
