package modules.users.application.authenticateconsoleuser;

import java.util.Map;

import modules.shared.application.AbstractInputDto;

import static modules.shared.application.Primitives.getTrimmedString;

public final class AuthenticateConsoleUserDto extends AbstractInputDto {

    private final String userUuid;

    private AuthenticateConsoleUserDto(Map<String, Object> primitives) {
        super(primitives);

        this.userUuid = getTrimmedString(primitives.get("userUuid"));
    }

    public static AuthenticateConsoleUserDto fromPrimitives(Map<String, Object> primitives) {
        return new AuthenticateConsoleUserDto(primitives);
    }

    public String getUserUuid() {
        return userUuid;
    }
}
