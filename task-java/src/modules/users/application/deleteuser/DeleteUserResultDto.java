package modules.users.application.deleteuser;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.users.domain.enums.UsersKeyEnum;

import static modules.shared.application.Primitives.getInt;
import static modules.shared.application.Primitives.getTrimmedString;

public final class DeleteUserResultDto {

    private final String userUuid;
    private final int affected;

    private DeleteUserResultDto(Map<String, Object> primitives) {
        this.userUuid = getTrimmedString(primitives.get("userUuid"));
        this.affected = getInt(primitives.get("affected"));
    }

    public static DeleteUserResultDto fromPrimitives(Map<String, Object> primitives) {
        return new DeleteUserResultDto(primitives);
    }

    public String getUserUuid() {
        return userUuid;
    }

    public int getAffected() {
        return affected;
    }

    public Map<String, Object> toArray() {
        return Primitives.of(
            UsersKeyEnum.USER_UUID, userUuid,
            UsersKeyEnum.AFFECTED, affected
        );
    }
}
