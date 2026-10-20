import { ScreenEnum } from "/js/Modules/Shared/Domain/Enums/ScreenEnum.js";
import { SharedEventEnum } from "/js/Modules/Shared/Domain/Enums/SharedEventEnum.js";
import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { Translator } from "/js/Modules/Shared/Infrastructure/Components/Translator/Translator.js";

const CSS_HIDDEN = "hidden";
const CSS_ACTIVE = "active";

/**
 * Menu de pantallas (por el hash de la url: #tasks, #account) y selector de idioma. Cada pantalla es una
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
        };
        window.addEventListener("hashchange", () => this.#renderCurrentScreen());
        this.#elements.selectLanguage.addEventListener("change", () => this.#dispatcher.dispatch(
            SharedEventEnum.LANGUAGE_CHANGE_REQUESTED,
            { languageCode: this.#elements.selectLanguage.value }
        ));
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
