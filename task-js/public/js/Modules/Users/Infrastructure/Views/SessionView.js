import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";

import { UsersEventEnum } from "/js/Modules/Users/Domain/Enums/UsersEventEnum.js";

const CSS_HIDDEN = "hidden";

/**
 * Cabecera con la sesion abierta: quien es el usuario y el boton de salir.
 */
export class SessionView {

    static #instance = null;

    #dispatcher;
    #elements = {};

    constructor() {
        this.#dispatcher = Dispatcher.getInstance();
    }

    /**
     * @returns {SessionView}
     */
    static getInstance() {
        SessionView.#instance ??= new SessionView();
        return SessionView.#instance;
    }

    init() {
        this.#elements = {
            divSession: document.getElementById("div-session"),
            spanSessionUser: document.getElementById("span-session-user"),
            buttonLogOut: document.getElementById("button-log-out"),
        };
        this.#elements.buttonLogOut.addEventListener("click", () => this.#dispatcher.dispatch(UsersEventEnum.LOG_OUT_REQUESTED));
    }

    /**
     * @param {{userName: string, userEmail: string}} user
     */
    show(user) {
        this.renderUser(user);
        this.#elements.divSession.classList.remove(CSS_HIDDEN);
    }

    hide() {
        this.#elements.divSession.classList.add(CSS_HIDDEN);
        this.#elements.spanSessionUser.textContent = "";
    }

    /**
     * @param {{userName: string, userEmail: string}} user
     */
    renderUser(user) {
        this.#elements.spanSessionUser.textContent = `${user.userName} · ${user.userEmail}`;
    }
}
