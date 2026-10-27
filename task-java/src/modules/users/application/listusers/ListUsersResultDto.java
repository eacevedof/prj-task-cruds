package modules.users.application.listusers;

import java.util.List;
import java.util.Map;

import static modules.shared.application.Primitives.getPrimitivesList;

public final class ListUsersResultDto {

    // lista de UserEntity.toPrimitives
    private final List<Map<String, Object>> users;

    private ListUsersResultDto(Map<String, Object> primitives) {
        this.users = getPrimitivesList(primitives.get("users"));
    }

    public static ListUsersResultDto fromPrimitives(Map<String, Object> primitives) {
        return new ListUsersResultDto(primitives);
    }

    public List<Map<String, Object>> getUsers() {
        return users;
    }

    public List<Map<String, Object>> toArray() {
        return users;
    }
}
