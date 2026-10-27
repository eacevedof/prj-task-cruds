package modules.users.domain.enums;

/**
 * Claves de la API (JSON de entrada y salida, argumentos GraphQL) de users, mas las columnas internas
 * con las que se monta UserEntity.
 */
public final class UsersKeyEnum {

    public static final String USER_UUID = "user_uuid";
    public static final String USER_NAME = "user_name";
    public static final String USER_EMAIL = "user_email";
    public static final String CREATED_AT = "created_at";
    public static final String UPDATED_AT = "updated_at";
    public static final String API_TOKEN = "api_token";
    public static final String PASSWORD = "password";
    public static final String AFFECTED = "affected";

    // columnas internas de BD: solo para montar UserEntity, nunca salen por la API
    public static final String ID = "id";
    public static final String PASSWORD_HASH = "password_hash";
    public static final String API_TOKEN_HASH = "api_token_hash";

    private UsersKeyEnum() {
    }
}
