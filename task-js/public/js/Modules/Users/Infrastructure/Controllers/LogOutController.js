import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { SessionStorer } from "/js/Modules/Shared/Infrastructure/Components/SessionStorer.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";
import { NavigationView } from "/js/Modules/Shared/Infrastructure/Views/NavigationView.js";

import { UsersEventEnum } from "/js/Modules/Users/Domain/Enums/UsersEventEnum.js";
import { UsersTextEnum } from "/js/Modules/Users/Domain/Enums/UsersTextEnum.js";
import { AuthView } from "/js/Modules/Users/Infrastructure/Views/AuthView.js";
import { SessionView } from "/js/Modules/Users/Infrastructure/Views/SessionView.js";

/**
 * Salir (a peticion del usuario, tras borrar la cuenta o porque la API respondio 401). No hay caso de uso:
 * cerrar sesion es borrar el token del navegador; la API no guarda sesiones.
 */
export class LogOutController {

    #dispatcher;
    #sessionStorer;
    #subscriber;
    #navigationView;
    #authView;
    #sessionView;

    constructor() {
        this.#dispatcher = Dispatcher.getInstance();
        this.#sessionStorer = SessionStorer.getInstance();
        this.#subscriber = Subscriber.getInstance();
        this.#navigationView = NavigationView.getInstance();
        this.#authView = AuthView.getInstance();
        this.#sessionView = SessionView.getInstance();
    }

    /**
     * @returns {LogOutController}
     */
    static getInstance() {
        return new LogOutController();
    }

    init() {
        this.#subscriber.subscribe(
            UsersEventEnum.LOG_OUT_REQUESTED,
            (request) => this.invoke(request.messageKey ?? UsersTextEnum.LOGGED_OUT)
        );
        this.#subscriber.subscribe(UsersEventEnum.SESSION_EXPIRED, () => this.invoke(UsersTextEnum.SESSION_EXPIRED));
    }

    /**
     * @param {string} messageKey aviso para la pantalla de entrada (clave del Translator)
     */
    invoke(messageKey) {
        try {
            this.#sessionStorer.removeSession();
        }
        catch (error) {
            console.error("LogOutController.invoke", error);
        }
        this.#sessionView.hide();
        this.#navigationView.hide();
        this.#authView.show(messageKey);
        this.#dispatcher.dispatch(UsersEventEnum.USER_LOGGED_OUT);
    }
}
