export const SharedEventEnum = Object.freeze({
    LANGUAGE_CHANGE_REQUESTED: "shared.language-change-requested",
    // el idioma ya cambio: las vistas repintan lo que generan por js (tablas, selects)
    LANGUAGE_CHANGED: "shared.language-changed",
    // {backendCode}: el usuario elige otro back (php, go, rust); la pagina se recarga contra el
    BACKEND_CHANGE_REQUESTED: "shared.backend-change-requested",
    // una llamada a la API empieza/termina (con exito o no): el modal de progreso se abre/cierra;
    // FINISHED lleva {operationName, durationMs, isOk} para el footer de tiempos de respuesta
    API_REQUEST_STARTED: "shared.api-request-started",
    API_REQUEST_FINISHED: "shared.api-request-finished",
});
