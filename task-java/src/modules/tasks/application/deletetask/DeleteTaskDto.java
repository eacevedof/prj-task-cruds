package modules.tasks.application.deletetask;

import java.util.Map;

import modules.shared.application.AbstractInputDto;

import static modules.shared.application.Primitives.getInt;
import static modules.shared.application.Primitives.getTrimmedString;

public final class DeleteTaskDto extends AbstractInputDto {

    private final int authUserId;
    private final String authUserUuid;
    private final String taskUuid;

    private DeleteTaskDto(Map<String, Object> primitives) {
        super(primitives);

        this.authUserId = getInt(primitives.get("authUserId"));
        this.authUserUuid = getTrimmedString(primitives.get("authUserUuid"));
        this.taskUuid = getTrimmedString(primitives.get("taskUuid"));
    }

    public static DeleteTaskDto fromPrimitives(Map<String, Object> primitives) {
        return new DeleteTaskDto(primitives);
    }

    public int getAuthUserId() {
        return authUserId;
    }

    public String getAuthUserUuid() {
        return authUserUuid;
    }

    public String getTaskUuid() {
        return taskUuid;
    }
}
