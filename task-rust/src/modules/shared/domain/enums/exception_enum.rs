/// codigos HTTP que usa la app (el catalogo completo de task-php se reduce a los que se usan).
pub struct ExceptionEnum;

impl ExceptionEnum {
    pub const CODE_OK: i64 = 200;
    pub const CODE_NO_CONTENT: i64 = 204;
    pub const CODE_BAD_REQUEST: i64 = 400;
    pub const CODE_UNAUTHORIZED: i64 = 401;
    pub const CODE_NOT_FOUND: i64 = 404;
    pub const CODE_METHOD_NOT_ALLOWED: i64 = 405;
    pub const CODE_CONFLICT: i64 = 409;
    pub const CODE_INTERNAL_SERVER_ERROR: i64 = 500;
}
