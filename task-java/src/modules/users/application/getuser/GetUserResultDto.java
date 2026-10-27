package modules.users.application.getuser;

import java.util.Map;

import static modules.shared.application.Primitives.getPrimitives;

public final class GetUserResultDto {

    // el usuario (UserEntity.toPrimitives)
    private final Map<String, Object> user;

    private GetUserResultDto(Map<String, Object> primitives) {
        this.user = getPrimitives(primitives.get("user"));
    }

    public static GetUserResultDto fromPrimitives(Map<String, Object> primitives) {
        return new GetUserResultDto(primitives);
    }

    public Map<String, Object> getUser() {
        return user;
    }

    public Map<String, Object> toArray() {
        return user;
    }
}
