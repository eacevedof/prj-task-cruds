package modules.users.application.updateuser;

import java.util.Map;

import static modules.shared.application.Primitives.getPrimitives;

public final class UpdateUserResultDto {

    // el usuario modificado (UserEntity.toPrimitives)
    private final Map<String, Object> user;

    private UpdateUserResultDto(Map<String, Object> primitives) {
        this.user = getPrimitives(primitives.get("user"));
    }

    public static UpdateUserResultDto fromPrimitives(Map<String, Object> primitives) {
        return new UpdateUserResultDto(primitives);
    }

    public Map<String, Object> getUser() {
        return user;
    }

    public Map<String, Object> toArray() {
        return user;
    }
}
