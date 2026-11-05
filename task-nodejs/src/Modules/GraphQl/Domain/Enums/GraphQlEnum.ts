/**
 * Claves del protocolo GraphQL sobre HTTP (body de la peticion y errores de la respuesta).
 */
export const GraphQlEnum = Object.freeze({
    KEY_QUERY: "query",
    KEY_VARIABLES: "variables",
    KEY_OPERATION_NAME: "operationName",
    KEY_ERRORS: "errors",
    KEY_MESSAGE: "message",
    KEY_DATA: "data",

    // tipos raiz del protocolo; los de negocio (User…) los declara el provider del modulo que los define
    TYPE_QUERY: "Query",
    TYPE_MUTATION: "Mutation",

    EXTENSION_CODE: "code",
    EXTENSION_ERRORS: "errors",

    // User -> tasks -> campo es profundidad 3: 5 deja margen sin abrir la puerta a consultas abusivas
    MAX_QUERY_DEPTH: 5,
});
