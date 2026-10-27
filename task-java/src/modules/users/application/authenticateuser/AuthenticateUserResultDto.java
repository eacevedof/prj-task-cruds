package modules.users.application.authenticateuser;

import java.util.Map;

import modules.shared.application.Primitives;

import static modules.shared.application.Primitives.getInt;
import static modules.shared.application.Primitives.getTrimmedString;

/**
 * userId es el que usan todas las queries (mas ligero que el uuid); userUuid solo para comparar con el
 * user_uuid que llega de fuera (argumento GraphQL, opcion de consola).
 */
public final class AuthenticateUserResultDto {

    private final int userId;
    private final String userUuid;

    private AuthenticateUserResultDto(int userId, String userUuid) {
        this.userId = userId;
        this.userUuid = userUuid;
    }

    public static AuthenticateUserResultDto fromPrimitives(Map<String, Object> primitives) {
        return new AuthenticateUserResultDto(
            getInt(primitives.get("userId")),
            getTrimmedString(primitives.get("userUuid"))
        );
    }

    public int getUserId() {
        return userId;
    }

    public String getUserUuid() {
        return userUuid;
    }

    public Map<String, Object> toAuthPrimitives() {
        return Primitives.of(
            "authUserId", userId,
            "authUserUuid", userUuid
        );
    }
}
