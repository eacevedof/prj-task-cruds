export const SharedEventEnum = Object.freeze({
    LANGUAGE_CHANGE_REQUESTED: "shared.language-change-requested",
    // el idioma ya cambio: las vistas repintan lo que generan por js (tablas, selects)
    LANGUAGE_CHANGED: "shared.language-changed",
    // una llamada a la API empieza/termina (con exito o no): el modal de progreso se abre/cierra
    API_REQUEST_STARTED: "shared.api-request-started",
    API_REQUEST_FINISHED: "shared.api-request-finished",
});
