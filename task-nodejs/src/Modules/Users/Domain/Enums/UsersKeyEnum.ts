/**
 * Claves de la API (JSON de entrada y salida, argumentos GraphQL) de users, mas las columnas internas
 * con las que se monta UserEntity.
 */
export const UsersKeyEnum = Object.freeze({
    USER_UUID: "user_uuid",
    USER_NAME: "user_name",
    USER_EMAIL: "user_email",
    CREATED_AT: "created_at",
    UPDATED_AT: "updated_at",
    API_TOKEN: "api_token",
    PASSWORD: "password",
    AFFECTED: "affected",

    // columnas internas de BD: solo para montar UserEntity, nunca salen por la API
    ID: "id",
    PASSWORD_HASH: "password_hash",
    API_TOKEN_HASH: "api_token_hash",
});
