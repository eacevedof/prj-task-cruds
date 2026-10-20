import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { Htmler } from "/js/Modules/Shared/Infrastructure/Components/Htmler.js";
import { Translator } from "/js/Modules/Shared/Infrastructure/Components/Translator/Translator.js";

import { UsersEventEnum } from "/js/Modules/Users/Domain/Enums/UsersEventEnum.js";

const CSS_HIDDEN = "hidden";
const CSS_ERROR = "message-error";

/**
 * Pantalla de entrada: login con user_name + password o crear cuenta (que entra directamente).
 * Una sola instancia: guarda las referencias al DOM.
 */
export class AuthView {

    static #instance = null;

    #dispatcher;
    #htmler;
    #translator;
    #elements = {};

    constructor() {
        this.#dispatcher = Dispatcher.getInstance();
        this.#htmler = Htmler.getInstance();
        this.#translator = Translator.getInstance();
    }

    /**
     * @returns {AuthView}
     */
    static getInstance() {
        AuthView.#instance ??= new AuthView();
        return AuthView.#instance;
    }

    init() {
        this.#loadElements();
        this.#elements.formLogIn.addEventListener("submit", (event) => this.#onLogInSubmit(event));
        this.#elements.formSignUp.addEventListener("submit", (event) => this.#onSignUpSubmit(event));
    }

    /**
     * @param {string} messageKey aviso opcional (UsersTextEnum): sesion caducada, cuenta borrada...
     */
    show(messageKey = "") {
        this.#elements.sectionAuth.classList.remove(CSS_HIDDEN);
        this.#elements.formLogIn.reset();
        this.#elements.formSignUp.reset();
        this.renderBusy(false);
        this.#renderMessage(messageKey ? this.#translator.getText(messageKey) : "", false);
        this.#elements.inputLogInUserName.focus();
    }

    hide() {
        this.#elements.sectionAuth.classList.add(CSS_HIDDEN);
        this.#renderMessage("", false);
        // la password no se queda en el DOM al entrar
        this.#elements.formLogIn.reset();
        this.#elements.formSignUp.reset();
    }

    /**
     * @param {boolean} isBusy
     */
    renderBusy(isBusy) {
        this.#elements.sectionAuth.querySelectorAll("button").forEach((button) => button.disabled = isBusy);
    }

    /**
     * @param {string[]} errors
     */
    renderErrors(errors) {
        // al restaurar una sesion la pantalla esta oculta: si falla hay que volver a ella
        this.#elements.sectionAuth.classList.remove(CSS_HIDDEN);
        this.renderBusy(false);
        this.#renderMessage(errors.join("\n"), true);
    }

    #loadElements() {
        this.#elements = {
            sectionAuth: document.getElementById("section-auth"),
            divAuthMessage: document.getElementById("div-auth-message"),
            formLogIn: document.getElementById("form-log-in"),
            inputLogInUserName: document.getElementById("input-log-in-user-name"),
            inputLogInPassword: document.getElementById("input-log-in-password"),
            formSignUp: document.getElementById("form-sign-up"),
            inputSignUpUserName: document.getElementById("input-sign-up-user-name"),
            inputSignUpUserEmail: document.getElementById("input-sign-up-user-email"),
            inputSignUpPassword: document.getElementById("input-sign-up-password"),
        };
    }

    #onLogInSubmit(event) {
        event.preventDefault();
        this.renderBusy(true);
        this.#dispatcher.dispatch(UsersEventEnum.LOG_IN_REQUESTED, {
            userName: this.#elements.inputLogInUserName.value,
            password: this.#elements.inputLogInPassword.value,
        });
    }

    #onSignUpSubmit(event) {
        event.preventDefault();
        this.renderBusy(true);
        this.#dispatcher.dispatch(UsersEventEnum.SIGN_UP_REQUESTED, {
            userName: this.#elements.inputSignUpUserName.value,
            userEmail: this.#elements.inputSignUpUserEmail.value,
            password: this.#elements.inputSignUpPassword.value,
        });
    }

    #renderMessage(message, isError) {
        this.#elements.divAuthMessage.innerHTML = this.#htmler.getEscaped(message).replaceAll("\n", "<br>");
        this.#elements.divAuthMessage.classList.toggle(CSS_HIDDEN, !message);
        this.#elements.divAuthMessage.classList.toggle(CSS_ERROR, isError);
    }
}
