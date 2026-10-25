/**
 * Modal con barra de progreso mientras haya alguna llamada a la API en curso. Lleva la cuenta de las
 * pendientes: con dos a la vez se cierra al acabar la ultima, no la primera.
 */
export class RequestProgressView {

    static #instance = null;

    #elements = {};
    #pendingRequests = 0;

    /**
     * @returns {RequestProgressView}
     */
    static getInstance() {
        RequestProgressView.#instance ??= new RequestProgressView();
        return RequestProgressView.#instance;
    }

    init() {
        this.#elements = {
            dialogRequestProgress: document.getElementById("dialog-request-progress"),
        };
        // Esc cerraria el modal con la peticion aun en vuelo
        this.#elements.dialogRequestProgress.addEventListener("cancel", (event) => event.preventDefault());
    }

    addPendingRequest() {
        this.#pendingRequests++;
        if (this.#pendingRequests > 1) return;

        // <dialog>: modal nativo (el resto de la pagina queda inerte); sin soporte, al menos visible
        if (typeof this.#elements.dialogRequestProgress.showModal === "function") {
            this.#elements.dialogRequestProgress.showModal();
            return;
        }
        this.#elements.dialogRequestProgress.setAttribute("open", "");
    }

    removePendingRequest() {
        this.#pendingRequests = Math.max(0, this.#pendingRequests - 1);
        if (this.#pendingRequests > 0) return;

        if (typeof this.#elements.dialogRequestProgress.close === "function") {
            this.#elements.dialogRequestProgress.close();
            return;
        }
        this.#elements.dialogRequestProgress.removeAttribute("open");
    }
}
