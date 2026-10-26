/// claves del protocolo GraphQL sobre HTTP (body de la peticion y errores de la respuesta).
pub struct GraphqlEnum;

impl GraphqlEnum {
    pub const KEY_QUERY: &'static str = "query";
    pub const KEY_VARIABLES: &'static str = "variables";
    pub const KEY_OPERATION_NAME: &'static str = "operationName";
    pub const KEY_DATA: &'static str = "data";
    pub const KEY_ERRORS: &'static str = "errors";
    pub const KEY_MESSAGE: &'static str = "message";
    pub const KEY_LOCATIONS: &'static str = "locations";
    pub const KEY_PATH: &'static str = "path";
    pub const KEY_EXTENSIONS: &'static str = "extensions";

    pub const EXTENSION_CODE: &'static str = "code";
    pub const EXTENSION_ERRORS: &'static str = "errors";

    /// User -> tasks -> campo es profundidad 3: 5 deja margen sin abrir la puerta a consultas abusivas
    pub const MAX_QUERY_DEPTH: usize = 5;
}
