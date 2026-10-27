package modules.users.application.updateuser;

import java.util.Map;

import modules.shared.application.AbstractInputDto;

import static modules.shared.application.Primitives.getInt;
import static modules.shared.application.Primitives.getTrimmedString;

public final class UpdateUserDto extends AbstractInputDto {

    private final int authUserId;
    private final String authUserUuid;
    private final String userUuid;
    private final String userName;
    private final String userEmail;

    private UpdateUserDto(Map<String, Object> primitives) {
        super(primitives);

        this.authUserId = getInt(primitives.get("authUserId"));
        this.authUserUuid = getTrimmedString(primitives.get("authUserUuid"));
        this.userUuid = getTrimmedString(primitives.get("userUuid"));
        this.userName = getTrimmedString(primitives.get("userName"));
        this.userEmail = getTrimmedString(primitives.get("userEmail"));
    }

    public static UpdateUserDto fromPrimitives(Map<String, Object> primitives) {
        return new UpdateUserDto(primitives);
    }

    public int getAuthUserId() {
        return authUserId;
    }

    public String getAuthUserUuid() {
        return authUserUuid;
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
}
