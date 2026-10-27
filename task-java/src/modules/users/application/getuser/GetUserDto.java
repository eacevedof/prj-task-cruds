package modules.users.application.getuser;

import java.util.Map;

import modules.shared.application.AbstractInputDto;

import static modules.shared.application.Primitives.getInt;
import static modules.shared.application.Primitives.getTrimmedString;

public final class GetUserDto extends AbstractInputDto {

    private final int authUserId;
    private final String authUserUuid;
    private final String userUuid;

    private GetUserDto(Map<String, Object> primitives) {
        super(primitives);

        this.authUserId = getInt(primitives.get("authUserId"));
        this.authUserUuid = getTrimmedString(primitives.get("authUserUuid"));
        this.userUuid = getTrimmedString(primitives.get("userUuid"));
    }

    public static GetUserDto fromPrimitives(Map<String, Object> primitives) {
        return new GetUserDto(primitives);
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
}
