import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { Htmler } from "/js/Modules/Shared/Infrastructure/Components/Htmler.js";
import { Translator } from "/js/Modules/Shared/Infrastructure/Components/Translator/Translator.js";

import { AccountEventEnum } from "/js/Modules/Account/Domain/Enums/AccountEventEnum.js";
import { AccountTextEnum } from "/js/Modules/Account/Domain/Enums/AccountTextEnum.js";

const CSS_HIDDEN = "hidden";
const CSS_ERROR = "message-error";

/**
 * Pantalla "Cuenta": modificar datos, rotar el api_token y borrar la cuenta.
 */
export class AccountView {

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
     * @returns {AccountView}
     */
    static getInstance() {
        AccountView.#instance ??= new AccountView();
        return AccountView.#instance;
    }

    init() {
        this.#elements = {
            sectionAccount: document.getElementById("section-account"),
            divAccountMessage: document.getElementById("div-account-message"),
            formAccount: document.getElementById("form-account"),
            inputAccountUserName: document.getElementById("input-account-user-name"),
            inputAccountUserEmail: document.getElementById("input-account-user-email"),
            buttonRegenerateApiToken: document.getElementById("button-regenerate-api-token"),
            divAccountApiToken: document.getElementById("div-account-api-token"),
            codeAccountApiToken: document.getElementById("code-account-api-token"),
            buttonCopyAccountApiToken: document.getElementById("button-copy-account-api-token"),
            buttonDeleteAccount: document.getElementById("button-delete-account"),
        };
        this.#elements.formAccount.addEventListener("submit", (event) => this.#onAccountSubmit(event));
        this.#elements.buttonRegenerateApiToken.addEventListener("click", () => this.#onRegenerateApiTokenClick());
        this.#elements.buttonCopyAccountApiToken.addEventListener("click", () => this.#onCopyAccountApiTokenClick());
        this.#elements.buttonDeleteAccount.addEventListener("click", () => this.#onDeleteAccountClick());
    }

    /**
     * Al entrar o salir: sin avisos ni token a la vista.
     */
    reset() {
        this.renderBusy(false);
        this.#renderMessage("", false);
        this.#elements.divAccountApiToken.classList.add(CSS_HIDDEN);
        this.#elements.codeAccountApiToken.textContent = "";
    }

    /**
     * @param {{userName: string, userEmail: string}} user
     */
    renderUser(user) {
        this.#elements.inputAccountUserName.value = user.userName;
        this.#elements.inputAccountUserEmail.value = user.userEmail;
    }

    /**
     * @param {boolean} isBusy
     */
    renderBusy(isBusy) {
        this.#elements.sectionAccount.querySelectorAll("button").forEach((button) => button.disabled = isBusy);
    }

    /**
     * @param {string} textKey AccountTextEnum
     */
    renderSuccess(textKey) {
        this.renderBusy(false);
        this.#renderMessage(this.#translator.getText(textKey), false);
    }

    /**
     * @param {string[]} errors
     */
    renderErrors(errors) {
        this.renderBusy(false);
        this.#renderMessage(errors.join("\n"), true);
    }

    /**
     * @param {string} apiToken el nuevo, que solo se ve ahora
     */
    renderNewApiToken(apiToken) {
        this.renderBusy(false);
        this.#renderMessage("", false);
        this.#elements.codeAccountApiToken.textContent = apiToken;
        this.#elements.buttonCopyAccountApiToken.textContent = this.#translator.getText(AccountTextEnum.COPY);
        this.#elements.divAccountApiToken.classList.remove(CSS_HIDDEN);
    }

    #onAccountSubmit(event) {
        event.preventDefault();
        this.renderBusy(true);
        this.#dispatcher.dispatch(AccountEventEnum.UPDATE_ACCOUNT_REQUESTED, {
            userName: this.#elements.inputAccountUserName.value,
            userEmail: this.#elements.inputAccountUserEmail.value,
        });
    }

    #onRegenerateApiTokenClick() {
        if (!window.confirm(this.#translator.getText(AccountTextEnum.CONFIRM_REGENERATE_API_TOKEN))) return;
        this.renderBusy(true);
        this.#dispatcher.dispatch(AccountEventEnum.REGENERATE_API_TOKEN_REQUESTED);
    }

    #onCopyAccountApiTokenClick() {
        navigator.clipboard?.writeText(this.#elements.codeAccountApiToken.textContent)
            .then(() => this.#elements.buttonCopyAccountApiToken.textContent = this.#translator.getText(AccountTextEnum.COPIED));
    }

    #onDeleteAccountClick() {
        if (!window.confirm(this.#translator.getText(AccountTextEnum.CONFIRM_DELETE))) return;
        this.renderBusy(true);
        this.#dispatcher.dispatch(AccountEventEnum.DELETE_ACCOUNT_REQUESTED);
    }

    #renderMessage(message, isError) {
        this.#elements.divAccountMessage.innerHTML = this.#htmler.getEscaped(message).replaceAll("\n", "<br>");
        this.#elements.divAccountMessage.classList.toggle(CSS_HIDDEN, !message);
        this.#elements.divAccountMessage.classList.toggle(CSS_ERROR, isError);
    }
}
