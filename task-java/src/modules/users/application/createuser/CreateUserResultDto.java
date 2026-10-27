package modules.users.application.createuser;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.users.domain.enums.UsersKeyEnum;

import static modules.shared.application.Primitives.getPrimitives;
import static modules.shared.application.Primitives.getTrimmedString;

public final class CreateUserResultDto {

    // el usuario creado (UserEntity.toPrimitives) + su api_token en claro, solo aqui
    private final Map<String, Object> user;
    private final String apiToken;

    private CreateUserResultDto(Map<String, Object> primitives) {
        this.user = getPrimitives(primitives.get("user"));
        this.apiToken = getTrimmedString(primitives.get("apiToken"));
    }

    public static CreateUserResultDto fromPrimitives(Map<String, Object> primitives) {
        return new CreateUserResultDto(primitives);
    }

    public Map<String, Object> getUser() {
        return user;
    }

    public String getApiToken() {
        return apiToken;
    }

    public Map<String, Object> toArray() {
        return Primitives.merge(user, Primitives.of(UsersKeyEnum.API_TOKEN, apiToken));
    }
}
