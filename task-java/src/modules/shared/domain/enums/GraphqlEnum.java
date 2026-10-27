package modules.shared.domain.enums;

/**
 * Claves del protocolo GraphQL sobre HTTP (body de la peticion y errores de la respuesta).
 */
public final class GraphqlEnum {

    public static final String KEY_QUERY = "query";
    public static final String KEY_VARIABLES = "variables";
    public static final String KEY_OPERATION_NAME = "operationName";
    public static final String KEY_ERRORS = "errors";
    public static final String KEY_MESSAGE = "message";
    public static final String KEY_DATA = "data";
    public static final String KEY_LOCATIONS = "locations";
    public static final String KEY_PATH = "path";
    public static final String KEY_EXTENSIONS = "extensions";

    // tipos de schema.graphql que tienen resolvers propios
    public static final String TYPE_QUERY = "Query";
    public static final String TYPE_MUTATION = "Mutation";
    public static final String TYPE_USER = "User";

    public static final String EXTENSION_CODE = "code";
    public static final String EXTENSION_ERRORS = "errors";

    // User -> tasks -> campo es profundidad 3: 5 deja margen sin abrir la puerta a consultas abusivas
    public static final int MAX_QUERY_DEPTH = 5;

    private GraphqlEnum() {
    }
}
