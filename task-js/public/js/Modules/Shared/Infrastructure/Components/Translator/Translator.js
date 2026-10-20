import { TextsEn } from "/js/Modules/Shared/Infrastructure/Components/Translator/TextsEn.js";
import { TextsEs } from "/js/Modules/Shared/Infrastructure/Components/Translator/TextsEs.js";

const DEFAULT_LANGUAGE_CODE = "en";
const TEXTS_BY_LANGUAGE_CODE = Object.freeze({
    en: TextsEn,
    es: TextsEs,
});

/**
 * Textos de la interfaz en el idioma activo (ingles por defecto). En el html: data-i18n="clave" (texto) y
 * data-i18n-placeholder="clave". Una sola instancia: guarda el idioma activo (quien lo persiste es el controller).
 * Componente autocontenido: sus diccionarios viven en su misma carpeta.
 */
export class Translator {

    static #instance = null;

    #languageCode = DEFAULT_LANGUAGE_CODE;

    /**
     * @returns {Translator}
     */
    static getInstance() {
        Translator.#instance ??= new Translator();
        return Translator.#instance;
    }

    /**
     * @returns {string}
     */
    getLanguageCode() {
        return this.#languageCode;
    }

    /**
     * Un idioma sin diccionario cae en el de por defecto.
     * @param {string} languageCode
     */
    setLanguageCode(languageCode) {
        this.#languageCode = TEXTS_BY_LANGUAGE_CODE[languageCode] ? languageCode : DEFAULT_LANGUAGE_CODE;
    }

    /**
     * @param {string} key
     * @param {Object<string, string>} replacements {title: "x"} sustituye "{title}"
     * @returns {string} sin traduccion: ingles; sin ingles: la propia clave (se ve el hueco, no rompe)
     */
    getText(key, replacements = {}) {
        const text = TEXTS_BY_LANGUAGE_CODE[this.#languageCode][key] ?? TextsEn[key] ?? key;
        return Object.entries(replacements).reduce(
            (replacedText, [name, value]) => replacedText.replaceAll(`{${name}}`, String(value)),
            text
        );
    }

    /**
     * @param {ParentNode} root
     */
    translateElement(root) {
        root.querySelectorAll("[data-i18n]").forEach((element) => {
            element.textContent = this.getText(element.dataset.i18n);
        });
        root.querySelectorAll("[data-i18n-placeholder]").forEach((element) => {
            element.placeholder = this.getText(element.dataset.i18nPlaceholder);
        });
    }
}
