import { ScreenEnum } from "/js/Modules/Shared/Domain/Enums/ScreenEnum.js";
import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { SessionStorer } from "/js/Modules/Shared/Infrastructure/Components/SessionStorer.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";
import { NavigationView } from "/js/Modules/Shared/Infrastructure/Views/NavigationView.js";

import { UsersEventEnum } from "/js/Modules/Users/Domain/Enums/UsersEventEnum.js";
import { AuthView } from "/js/Modules/Users/Infrastructure/Views/AuthView.js";
import { SessionView } from "/js/Modules/Users/Infrastructure/Views/SessionView.js";

/**
 * Entrar, venga de donde venga el api_token (login, alta o sesion recuperada): se guarda la sesion, se abre la
 * pantalla de tareas y se avisa al resto de modulos (USER_LOGGED_IN). No hay caso de uso: no llama a la API.
 */
export class StartSessionController {

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
     * @returns {StartSessionController}
     */
    static getInstance() {
        return new StartSessionController();
    }

    init() {
        this.#subscriber.subscribe(UsersEventEnum.SESSION_STARTED, (request) => this.invoke(request));
    }

    /**
     * @param {{apiToken: string, userUuid: string, userName: string, userEmail: string}} request
     */
    invoke(request) {
        const user = {
            userUuid: request.userUuid,
            userName: request.userName,
            userEmail: request.userEmail,
        };
        try {
            this.#sessionStorer.saveSession({ apiToken: request.apiToken, ...user });
        }
        catch (error) {
            // sin sessionStorage se entra igual, pero al recargar habra que volver a hacer login
            console.error("StartSessionController.invoke", error);
        }

        this.#authView.hide();
        this.#sessionView.show(user);
        this.#navigationView.show();
        this.#navigationView.goToScreen(ScreenEnum.TASKS);
        this.#dispatcher.dispatch(UsersEventEnum.USER_LOGGED_IN, user);
    }
}
