import { ScreenEnum } from "/js/Modules/Shared/Domain/Enums/ScreenEnum.js";
import { SharedEventEnum } from "/js/Modules/Shared/Domain/Enums/SharedEventEnum.js";
import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { Translator } from "/js/Modules/Shared/Infrastructure/Components/Translator/Translator.js";

const CSS_HIDDEN = "hidden";
const CSS_ACTIVE = "active";
const BACKEND_LOGO_PATH = "/img/backends";
// donde publica cada back su documentacion (la misma ruta en php, go y rust)
const API_DOCUMENTATION_PATH = "/api/v1";

/**
 * Menu de pantallas (por el hash de la url: #tasks, #account), selector de back y de idioma. Cada pantalla es una
 * section con data-screen; solo se ve la del hash y solo con sesion (show/hide).
 */
export class NavigationView {

    static #instance = null;

    #dispatcher;
    #translator;
    #elements = {};
    #isVisible = false;

    constructor() {
        this.#dispatcher = Dispatcher.getInstance();
        this.#translator = Translator.getInstance();
    }

    /**
     * @returns {NavigationView}
     */
    static getInstance() {
        NavigationView.#instance ??= new NavigationView();
        return NavigationView.#instance;
    }

    init() {
        this.#elements = {
            navScreens: document.getElementById("nav-screens"),
            screenLinks: [...document.querySelectorAll("#nav-screens a[data-screen]")],
            screens: [...document.querySelectorAll("section[data-screen]")],
            selectLanguage: document.getElementById("select-language"),
            divBackends: document.getElementById("div-backends"),
            aApiDocumentation: document.getElementById("a-api-documentation"),
        };
        window.addEventListener("hashchange", () => this.#renderCurrentScreen());
        this.#elements.selectLanguage.addEventListener("change", () => this.#dispatcher.dispatch(
            SharedEventEnum.LANGUAGE_CHANGE_REQUESTED,
            { languageCode: this.#elements.selectLanguage.value }
        ));
        this.#elements.divBackends.addEventListener("click", (event) => {
            const buttonBackend = event.target.closest("button[data-backend-code]");
            if (!buttonBackend || buttonBackend.getAttribute("aria-checked") === "true") return;
            this.#dispatcher.dispatch(
                SharedEventEnum.BACKEND_CHANGE_REQUESTED,
                { backendCode: buttonBackend.dataset.backendCode }
            );
        });
    }

    /**
     * Con sesion: menu visible y la pantalla del hash (o la de por defecto).
     */
    show() {
        this.#isVisible = true;
        this.#elements.navScreens.classList.remove(CSS_HIDDEN);
        this.#renderCurrentScreen();
    }

    hide() {
        this.#isVisible = false;
        this.#elements.navScreens.classList.add(CSS_HIDDEN);
        this.#elements.screens.forEach((screen) => screen.classList.add(CSS_HIDDEN));
    }

    /**
     * @param {string} screen ScreenEnum
     */
    goToScreen(screen) {
        if (window.location.hash === `#${screen}`) {
            this.#renderCurrentScreen();
            return;
        }
        window.location.hash = screen;
    }

    /**
     * Idioma ya fijado en el Translator: textos fijos del html, atributo lang y selector.
     */
    renderLanguage() {
        const languageCode = this.#translator.getLanguageCode();
        document.documentElement.lang = languageCode;
        this.#elements.selectLanguage.value = languageCode;
        this.#translator.translateElement(document);
    }

    /**
     * Logos de los backs y, al lado, el enlace a la documentacion de la API del activo.
     * @param {{code: string, name: string, apiBaseUrl: string}[]} backends los de /config.json
     * @param {string} backendCode el activo
     */
    renderBackends(backends, backendCode) {
        this.#elements.divBackends.replaceChildren(
            ...backends.map((backend) => this.#createButtonBackend(backend, backend.code === backendCode))
        );

        const activeBackend = backends.find((backend) => backend.code === backendCode);
        if (!activeBackend) return;
        const apiDocumentationUrl = `${activeBackend.apiBaseUrl}${API_DOCUMENTATION_PATH}`;
        this.#elements.aApiDocumentation.href = apiDocumentationUrl;
        this.#elements.aApiDocumentation.title = `${activeBackend.name}: ${apiDocumentationUrl}`;
        this.#elements.aApiDocumentation.classList.remove(CSS_HIDDEN);
    }

    /**
     * Boton con el logo del back (/img/backends/<code>.svg); sin logo se queda el nombre.
     * @param {{code: string, name: string}} backend
     * @param {boolean} isActive
     * @returns {HTMLButtonElement}
     */
    #createButtonBackend(backend, isActive) {
        const buttonBackend = document.createElement("button");
        buttonBackend.type = "button";
        buttonBackend.dataset.backendCode = backend.code;
        buttonBackend.title = backend.name;
        buttonBackend.setAttribute("role", "radio");
        buttonBackend.setAttribute("aria-checked", String(isActive));
        buttonBackend.setAttribute("aria-label", backend.name);

        const imgLogo = document.createElement("img");
        imgLogo.src = `${BACKEND_LOGO_PATH}/${backend.code}.svg`;
        imgLogo.alt = "";
        imgLogo.addEventListener("error", () => imgLogo.replaceWith(backend.name), { once: true });
        buttonBackend.append(imgLogo);
        return buttonBackend;
    }

    /**
     * Otro back: se arranca de cero contra el (sesion, listados y caches son del anterior).
     */
    reloadPage() {
        window.location.reload();
    }

    #renderCurrentScreen() {
        if (!this.#isVisible) return;

        const requestedScreen = window.location.hash.replace("#", "");
        const currentScreen = Object.values(ScreenEnum).includes(requestedScreen) ? requestedScreen : ScreenEnum.DEFAULT;
        this.#elements.screens.forEach(
            (screen) => screen.classList.toggle(CSS_HIDDEN, screen.dataset.screen !== currentScreen)
        );
        this.#elements.screenLinks.forEach(
            (screenLink) => screenLink.classList.toggle(CSS_ACTIVE, screenLink.dataset.screen === currentScreen)
        );
    }
}
