import { LanguageCodeEnum } from "/js/Modules/Shared/Domain/Enums/LanguageCodeEnum.js";
import { SharedEventEnum } from "/js/Modules/Shared/Domain/Enums/SharedEventEnum.js";
import { StorageKeyEnum } from "/js/Modules/Shared/Domain/Enums/StorageKeyEnum.js";
import { Dispatcher } from "/js/Modules/Shared/Infrastructure/Components/Dispatcher.js";
import { LocalStorer } from "/js/Modules/Shared/Infrastructure/Components/LocalStorer.js";
import { Subscriber } from "/js/Modules/Shared/Infrastructure/Components/Subscriber.js";
import { Translator } from "/js/Modules/Shared/Infrastructure/Components/Translator/Translator.js";
import { NavigationView } from "/js/Modules/Shared/Infrastructure/Views/NavigationView.js";

/**
 * Idioma de la interfaz: preferencia del navegador (localStorage), no de la API, asi que no hay caso de uso.
 * Al arrancar aplica el guardado (o ingles, el de por defecto).
 */
export class ChangeLanguageController {

    #dispatcher;
    #localStorer;
    #subscriber;
    #translator;
    #navigationView;

    constructor() {
        this.#dispatcher = Dispatcher.getInstance();
        this.#localStorer = LocalStorer.getInstance();
        this.#subscriber = Subscriber.getInstance();
        this.#translator = Translator.getInstance();
        this.#navigationView = NavigationView.getInstance();
    }

    /**
     * @returns {ChangeLanguageController}
     */
    static getInstance() {
        return new ChangeLanguageController();
    }

    init() {
        this.#subscriber.subscribe(SharedEventEnum.LANGUAGE_CHANGE_REQUESTED, (request) => this.invoke(request));
        this.invoke({ languageCode: this.#getSavedLanguageCode() });
    }

    /**
     * @param {{languageCode: string}} request
     */
    invoke(request) {
        this.#translator.setLanguageCode(request.languageCode);
        try {
            this.#localStorer.setItem(StorageKeyEnum.LANGUAGE_CODE, this.#translator.getLanguageCode());
        }
        catch (error) {
            // sin localStorage el idioma vale para esta visita y no se recuerda
            console.error("ChangeLanguageController.invoke", error);
        }
        this.#navigationView.renderLanguage();
        this.#dispatcher.dispatch(SharedEventEnum.LANGUAGE_CHANGED);
    }

    #getSavedLanguageCode() {
        try {
            return this.#localStorer.getItem(StorageKeyEnum.LANGUAGE_CODE) ?? LanguageCodeEnum.DEFAULT;
        }
        catch (error) {
            console.error("ChangeLanguageController.getSavedLanguageCode", error);
            return LanguageCodeEnum.DEFAULT;
        }
    }
}
