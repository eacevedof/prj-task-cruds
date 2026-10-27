package modules.users.application.loginuser;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.users.domain.enums.UsersKeyEnum;

import static modules.shared.application.Primitives.getPrimitives;
import static modules.shared.application.Primitives.getTrimmedString;

public final class LogInUserResultDto {

    // el usuario (UserEntity.toPrimitives) + su api_token nuevo en claro, solo aqui
    private final Map<String, Object> user;
    private final String apiToken;

    private LogInUserResultDto(Map<String, Object> primitives) {
        this.user = getPrimitives(primitives.get("user"));
        this.apiToken = getTrimmedString(primitives.get("apiToken"));
    }

    public static LogInUserResultDto fromPrimitives(Map<String, Object> primitives) {
        return new LogInUserResultDto(primitives);
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
