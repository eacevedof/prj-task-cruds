package modules.users.application.authenticateuser;

import java.util.Map;

import modules.shared.application.AbstractInputDto;

import static modules.shared.application.Primitives.getTrimmedString;

public final class AuthenticateUserDto extends AbstractInputDto {

    private final String apiToken;

    private AuthenticateUserDto(Map<String, Object> primitives) {
        super(primitives);

        this.apiToken = getTrimmedString(primitives.get("apiToken"));
    }

    public static AuthenticateUserDto fromPrimitives(Map<String, Object> primitives) {
        return new AuthenticateUserDto(primitives);
    }

    public String getApiToken() {
        return apiToken;
    }
}
