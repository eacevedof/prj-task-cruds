/**
 * Claves del protocolo GraphQL sobre HTTP (body de la peticion y errores de la respuesta).
 */
export const GraphqlEnum = Object.freeze({
    KEY_QUERY: "query",
    KEY_VARIABLES: "variables",
    KEY_OPERATION_NAME: "operationName",
    KEY_ERRORS: "errors",
    KEY_MESSAGE: "message",
    KEY_DATA: "data",

    // tipos del schema que tienen resolvers propios
    TYPE_QUERY: "Query",
    TYPE_MUTATION: "Mutation",
    TYPE_USER: "User",

    EXTENSION_CODE: "code",
    EXTENSION_ERRORS: "errors",

    // User -> tasks -> campo es profundidad 3: 5 deja margen sin abrir la puerta a consultas abusivas
    MAX_QUERY_DEPTH: 5,
});
