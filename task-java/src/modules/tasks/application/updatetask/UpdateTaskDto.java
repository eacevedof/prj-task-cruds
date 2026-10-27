package modules.tasks.application.updatetask;

import java.util.Map;

import modules.shared.application.AbstractInputDto;

import static modules.shared.application.Primitives.getInt;
import static modules.shared.application.Primitives.getTrimmedString;

public final class UpdateTaskDto extends AbstractInputDto {

    private final int authUserId;
    private final String authUserUuid;
    private final String taskUuid;
    private final String taskTitle;
    private final String taskStatus;
    private final String taskType;
    private final String targetDate;
    private final String taskDescription;

    private UpdateTaskDto(Map<String, Object> primitives) {
        super(primitives);

        this.authUserId = getInt(primitives.get("authUserId"));
        this.authUserUuid = getTrimmedString(primitives.get("authUserUuid"));
        this.taskUuid = getTrimmedString(primitives.get("taskUuid"));
        this.taskTitle = getTrimmedString(primitives.get("taskTitle"));
        this.taskStatus = getTrimmedString(primitives.get("taskStatus"));
        this.taskType = getTrimmedString(primitives.get("taskType"));
        this.targetDate = getTrimmedString(primitives.get("targetDate"));
        this.taskDescription = getTrimmedString(primitives.get("taskDescription"));
    }

    public static UpdateTaskDto fromPrimitives(Map<String, Object> primitives) {
        return new UpdateTaskDto(primitives);
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

    public String getTaskTitle() {
        return taskTitle;
    }

    public String getTaskStatus() {
        return taskStatus;
    }

    public String getTaskType() {
        return taskType;
    }

    public String getTargetDate() {
        return targetDate;
    }

    public String getTaskDescription() {
        return taskDescription;
    }
}
