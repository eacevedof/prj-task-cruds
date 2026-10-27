package modules.tasks.application.gettask;

import java.util.Map;

import modules.shared.application.AbstractInputDto;

import static modules.shared.application.Primitives.getInt;
import static modules.shared.application.Primitives.getTrimmedString;

public final class GetTaskDto extends AbstractInputDto {

    private final int authUserId;
    private final String authUserUuid;
    private final String taskUuid;

    private GetTaskDto(Map<String, Object> primitives) {
        super(primitives);

        this.authUserId = getInt(primitives.get("authUserId"));
        this.authUserUuid = getTrimmedString(primitives.get("authUserUuid"));
        this.taskUuid = getTrimmedString(primitives.get("taskUuid"));
    }

    public static GetTaskDto fromPrimitives(Map<String, Object> primitives) {
        return new GetTaskDto(primitives);
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
