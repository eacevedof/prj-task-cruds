package enums

// GraphqlEnum claves del protocolo GraphQL sobre HTTP (body de la peticion y errores de la respuesta).
var GraphqlEnum = struct {
	KEY_QUERY          string
	KEY_VARIABLES      string
	KEY_OPERATION_NAME string
	KEY_ERRORS         string
	KEY_DATA           string
	KEY_MESSAGE        string

	// tipos del schema que tienen resolvers propios
	TYPE_QUERY    string
	TYPE_MUTATION string
	TYPE_USER     string

	EXTENSION_CODE   string
	EXTENSION_ERRORS string

	MAX_QUERY_DEPTH int
}{
	KEY_QUERY:          "query",
	KEY_VARIABLES:      "variables",
	KEY_OPERATION_NAME: "operationName",
	KEY_ERRORS:         "errors",
	KEY_DATA:           "data",
	KEY_MESSAGE:        "message",

	TYPE_QUERY:    "Query",
	TYPE_MUTATION: "Mutation",
	TYPE_USER:     "User",

	EXTENSION_CODE:   "code",
	EXTENSION_ERRORS: "errors",

	// User -> tasks -> campo es profundidad 3: 5 deja margen sin abrir la puerta a consultas abusivas
	MAX_QUERY_DEPTH: 5,
}
