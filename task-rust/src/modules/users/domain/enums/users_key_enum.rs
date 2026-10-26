/// claves de la API (JSON de entrada y salida, argumentos GraphQL) de users, mas las columnas internas con las que se
/// monta UserEntity.
pub struct UsersKeyEnum;

impl UsersKeyEnum {
    pub const USER_UUID: &'static str = "user_uuid";
    pub const USER_NAME: &'static str = "user_name";
    pub const USER_EMAIL: &'static str = "user_email";
    pub const CREATED_AT: &'static str = "created_at";
    pub const UPDATED_AT: &'static str = "updated_at";
    pub const API_TOKEN: &'static str = "api_token";
    pub const PASSWORD: &'static str = "password";
    pub const AFFECTED: &'static str = "affected";

    // columnas internas de BD: solo para montar UserEntity, nunca salen por la API
    pub const ID: &'static str = "id";
    pub const PASSWORD_HASH: &'static str = "password_hash";
    pub const API_TOKEN_HASH: &'static str = "api_token_hash";
}
