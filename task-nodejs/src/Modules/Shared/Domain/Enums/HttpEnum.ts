/**
 * Cabeceras, tipos y claves del envelope REST {code, status, errors, data}: sin literales sueltos en el transporte.
 */
export const HttpEnum = Object.freeze({
    HEADER_CONTENT_TYPE: "Content-Type",
    HEADER_WWW_AUTHENTICATE: "WWW-Authenticate",

    CONTENT_TYPE_JSON: "application/json",
    CONTENT_TYPE_HTML: "text/html; charset=utf-8",

    AUTH_SCHEME_BEARER: "Bearer",

    KEY_CODE: "code",
    KEY_STATUS: "status",
    KEY_ERRORS: "errors",
    KEY_DATA: "data",
});
