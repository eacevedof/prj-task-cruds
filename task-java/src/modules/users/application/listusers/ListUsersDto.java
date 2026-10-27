package modules.users.application.listusers;

import java.util.Map;

import modules.shared.application.AbstractInputDto;

import static modules.shared.application.Primitives.getInt;
import static modules.shared.application.Primitives.getTrimmedString;

public final class ListUsersDto extends AbstractInputDto {

    private final int authUserId;
    private final String authUserUuid;

    private ListUsersDto(Map<String, Object> primitives) {
        super(primitives);

        this.authUserId = getInt(primitives.get("authUserId"));
        this.authUserUuid = getTrimmedString(primitives.get("authUserUuid"));
    }

    public static ListUsersDto fromPrimitives(Map<String, Object> primitives) {
        return new ListUsersDto(primitives);
    }

    public int getAuthUserId() {
        return authUserId;
    }

    public String getAuthUserUuid() {
        return authUserUuid;
    }
}
