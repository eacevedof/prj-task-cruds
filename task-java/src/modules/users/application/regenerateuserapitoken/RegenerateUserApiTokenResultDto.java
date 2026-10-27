package modules.users.application.regenerateuserapitoken;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.users.domain.enums.UsersKeyEnum;

import static modules.shared.application.Primitives.getTrimmedString;

public final class RegenerateUserApiTokenResultDto {

    private final String userUuid;
    private final String apiToken;

    private RegenerateUserApiTokenResultDto(String userUuid, String apiToken) {
        this.userUuid = userUuid;
        this.apiToken = apiToken;
    }

    public static RegenerateUserApiTokenResultDto fromPrimitives(Map<String, Object> primitives) {
        return new RegenerateUserApiTokenResultDto(
            getTrimmedString(primitives.get("userUuid")),
            getTrimmedString(primitives.get("apiToken"))
        );
    }

    public String getUserUuid() {
        return userUuid;
    }

    public String getApiToken() {
        return apiToken;
    }

    public Map<String, Object> toArray() {
        return Primitives.of(
            UsersKeyEnum.USER_UUID, userUuid,
            UsersKeyEnum.API_TOKEN, apiToken
        );
    }
}
