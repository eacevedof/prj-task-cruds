import { Translator } from "/js/Modules/Shared/Infrastructure/Components/Translator/Translator.js";

const CSS_HIDDEN = "hidden";
// solo se pintan las ultimas; la media y la copia al portapapeles son de todas las de la sesion de la pagina
const MAX_ROWS = 50;
// umbrales del color de cada tiempo (ms)
const FAST_MS = 300;
const SLOW_MS = 1000;
// lo que dura el "Copiado" en el boton
const COPIED_FEEDBACK_MS = 1500;

/**
 * Footer fijo con el tiempo de respuesta de cada llamada a la API, la mas reciente arriba, el back activo
 * y el resumen (cuantas, media, la ultima). Copia todo el historico al portapapeles (texto con tabuladores:
 * se pega bien en una hoja de calculo). Las etiquetas son data-i18n del html: aqui solo se pintan numeros
 * y nombres, asi que no hay que repintar al cambiar de idioma.
 */
export class RequestTimesView {

    static #instance = null;

    #translator;
    #elements = {};
    #requestTimes = [];
    #backend = { name: "", apiBaseUrl: "" };

    constructor() {
        this.#translator = Translator.getInstance();
    }

    /**
     * @returns {RequestTimesView}
     */
    static getInstance() {
        RequestTimesView.#instance ??= new RequestTimesView();
        return RequestTimesView.#instance;
    }

    init() {
        this.#elements = {
            spanBackend: document.getElementById("span-request-times-backend"),
            spanCount: document.getElementById("span-request-times-count"),
            spanAverage: document.getElementById("span-request-times-average"),
            spanLast: document.getElementById("span-request-times-last"),
            pEmpty: document.getElementById("p-request-times-empty"),
            olRequestTimes: document.getElementById("ol-request-times"),
            buttonCopy: document.getElementById("button-copy-request-times"),
            buttonClear: document.getElementById("button-clear-request-times"),
        };
        // los botones viven en el summary: sin preventDefault tambien plegarian/desplegarian el footer
        this.#elements.buttonCopy.addEventListener("click", (event) => {
            event.preventDefault();
            this.#copyToClipboard();
        });
        this.#elements.buttonClear.addEventListener("click", (event) => {
            event.preventDefault();
            this.#clear();
        });
        this.#renderSummary();
    }

    /**
     * El back contra el que se miden los tiempos (cambiarlo recarga la pagina: el historico es de uno solo).
     * @param {{name: string, apiBaseUrl: string}} backend
     */
    renderBackend(backend) {
        this.#backend = { name: backend.name, apiBaseUrl: backend.apiBaseUrl };
        this.#elements.spanBackend.textContent = backend.name;
        this.#elements.spanBackend.title = backend.apiBaseUrl;
    }

    /**
     * @param {{operationName: string, durationMs: number, isOk: boolean, finishedAt: Date}} requestTime
     */
    addRequestTime(requestTime) {
        this.#requestTimes.push(requestTime);

        this.#elements.olRequestTimes.prepend(this.#createLiRequestTime(requestTime));
        while (this.#elements.olRequestTimes.children.length > MAX_ROWS) {
            this.#elements.olRequestTimes.lastElementChild.remove();
        }
        this.#elements.pEmpty.classList.add(CSS_HIDDEN);
        this.#renderSummary();
    }

    #clear() {
        this.#requestTimes = [];
        this.#elements.olRequestTimes.replaceChildren();
        this.#elements.pEmpty.classList.remove(CSS_HIDDEN);
        this.#renderSummary();
    }

    #copyToClipboard() {
        navigator.clipboard?.writeText(this.#getClipboardText())
            .then(() => {
                const copyText = this.#elements.buttonCopy.textContent;
                this.#elements.buttonCopy.textContent = this.#translator.getText("request_times.copied");
                setTimeout(() => this.#elements.buttonCopy.textContent = copyText, COPIED_FEEDBACK_MS);
            })
            .catch((error) => console.error("RequestTimesView.copyToClipboard", error));
    }

    /**
     * Cabecera (back, peticiones, media, ultima) y una linea por llamada, de la mas antigua a la mas nueva.
     * @returns {string}
     */
    #getClipboardText() {
        const summary = this.#getSummary();
        const lines = [
            this.#translator.getText("request_times.title"),
            `${this.#translator.getText("nav.backend")}\t${this.#backend.name}\t${this.#backend.apiBaseUrl}`,
            `${this.#translator.getText("request_times.count")}\t${summary.count}`,
            `${this.#translator.getText("request_times.average")}\t${this.#getMsText(summary.averageMs)}`,
            `${this.#translator.getText("request_times.last")}\t${this.#getMsText(summary.lastMs)}`,
            "",
            ["#", "time", "operation", "ms", "status"].join("\t"),
            ...this.#requestTimes.map((requestTime, index) => [
                index + 1,
                this.#getClockTime(requestTime.finishedAt),
                requestTime.operationName,
                requestTime.durationMs,
                requestTime.isOk ? "ok" : "error",
            ].join("\t")),
        ];
        return lines.join("\n");
    }

    #renderSummary() {
        const summary = this.#getSummary();
        this.#elements.spanCount.textContent = String(summary.count);
        this.#elements.spanAverage.textContent = this.#getMsText(summary.averageMs);
        this.#elements.spanLast.textContent = this.#getMsText(summary.lastMs);
        this.#elements.buttonCopy.disabled = summary.count === 0;
    }

    /**
     * @returns {{count: number, averageMs: number|null, lastMs: number|null}}
     */
    #getSummary() {
        const count = this.#requestTimes.length;
        if (!count) return { count, averageMs: null, lastMs: null };

        const totalMs = this.#requestTimes.reduce((sum, requestTime) => sum + requestTime.durationMs, 0);
        return { count, averageMs: Math.round(totalMs / count), lastMs: this.#requestTimes.at(-1).durationMs };
    }

    /**
     * @param {number|null} durationMs
     * @returns {string}
     */
    #getMsText(durationMs) {
        return durationMs === null ? "-" : `${durationMs} ms`;
    }

    /**
     * @param {{operationName: string, durationMs: number, isOk: boolean, finishedAt: Date}} requestTime
     * @returns {HTMLLIElement}
     */
    #createLiRequestTime(requestTime) {
        const liRequestTime = document.createElement("li");
        liRequestTime.classList.toggle("request-time-error", !requestTime.isOk);

        const timeFinishedAt = document.createElement("time");
        timeFinishedAt.dateTime = requestTime.finishedAt.toISOString();
        timeFinishedAt.textContent = this.#getClockTime(requestTime.finishedAt);

        const spanOperation = document.createElement("span");
        spanOperation.className = "request-time-operation";
        spanOperation.textContent = requestTime.operationName;

        const spanDuration = document.createElement("span");
        spanDuration.className = `request-time-duration ${this.#getSpeedCss(requestTime.durationMs)}`;
        spanDuration.textContent = `${requestTime.durationMs} ms`;

        liRequestTime.append(timeFinishedAt, spanOperation, spanDuration);
        return liRequestTime;
    }

    /**
     * @param {number} durationMs
     * @returns {string}
     */
    #getSpeedCss(durationMs) {
        if (durationMs < FAST_MS) return "speed-fast";
        if (durationMs < SLOW_MS) return "speed-medium";
        return "speed-slow";
    }

    /**
     * @param {Date} date
     * @returns {string} HH:MM:SS local
     */
    #getClockTime(date) {
        return [date.getHours(), date.getMinutes(), date.getSeconds()]
            .map((part) => String(part).padStart(2, "0"))
            .join(":");
    }
}
