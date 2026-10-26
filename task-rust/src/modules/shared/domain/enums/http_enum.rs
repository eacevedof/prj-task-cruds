/// cabeceras, tipos y claves del envelope REST {code, status, errors, data}: sin literales sueltos en el transporte.
pub struct HttpEnum;

impl HttpEnum {
    pub const HEADER_CONTENT_TYPE: &'static str = "Content-Type";
    pub const HEADER_WWW_AUTHENTICATE: &'static str = "WWW-Authenticate";

    pub const CONTENT_TYPE_JSON: &'static str = "application/json";
    pub const CONTENT_TYPE_HTML: &'static str = "text/html; charset=utf-8";

    pub const AUTH_SCHEME_BEARER: &'static str = "Bearer";

    pub const KEY_CODE: &'static str = "code";
    pub const KEY_STATUS: &'static str = "status";
    pub const KEY_ERRORS: &'static str = "errors";
    pub const KEY_DATA: &'static str = "data";
}
