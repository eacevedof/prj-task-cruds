package modules.users.domain.entities;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.users.domain.enums.UsersKeyEnum;

import static modules.shared.application.Primitives.getInt;
import static modules.shared.application.Primitives.getTrimmedString;

public final class UserEntity {

    public static final String USER_UUID_PREFIX = "user-";

    private final Integer id;
    private final String userUuid;
    private final String userName;
    private final String userEmail;
    private final String passwordHash;
    private final String apiTokenHash;
    private final String createdAt;
    private final String updatedAt;

    /**
     * Desde una fila de la tabla users (columnas en snake_case: contrato con la BD). Las lecturas no traen
     * password_hash ni api_token_hash: solo los informan las escrituras.
     */
    private UserEntity(Map<String, Object> primitives) {
        this.id = primitives.get(UsersKeyEnum.ID) != null ? getInt(primitives.get(UsersKeyEnum.ID)) : null;
        this.userUuid = getTrimmedString(primitives.get(UsersKeyEnum.USER_UUID));
        this.userName = getTrimmedString(primitives.get(UsersKeyEnum.USER_NAME));
        this.userEmail = getTrimmedString(primitives.get(UsersKeyEnum.USER_EMAIL));
        this.passwordHash = getTrimmedString(primitives.get(UsersKeyEnum.PASSWORD_HASH));
        this.apiTokenHash = getTrimmedString(primitives.get(UsersKeyEnum.API_TOKEN_HASH));
        this.createdAt = getTrimmedString(primitives.get(UsersKeyEnum.CREATED_AT));
        this.updatedAt = getTrimmedString(primitives.get(UsersKeyEnum.UPDATED_AT));
    }

    public static UserEntity fromPrimitives(Map<String, Object> primitives) {
        return new UserEntity(primitives);
    }

    /**
     * Representacion publica (API): el id interno no sale, el identificador es user_uuid. Los hashes nunca.
     */
    public Map<String, Object> toPrimitives() {
        return Primitives.of(
            UsersKeyEnum.USER_UUID, userUuid,
            UsersKeyEnum.USER_NAME, userName,
            UsersKeyEnum.USER_EMAIL, userEmail,
            UsersKeyEnum.CREATED_AT, createdAt,
            UsersKeyEnum.UPDATED_AT, updatedAt
        );
    }

    public Integer getId() {
        return id;
    }

    public String getUserUuid() {
        return userUuid;
    }

    public String getUserName() {
        return userName;
    }

    public String getUserEmail() {
        return userEmail;
    }

    public String getPasswordHash() {
        return passwordHash;
    }

    public String getApiTokenHash() {
        return apiTokenHash;
    }

    public String getCreatedAt() {
        return createdAt;
    }

    public String getUpdatedAt() {
        return updatedAt;
    }
}
