package modules.tasks.application.createtask;

import java.util.Map;

import modules.shared.application.AbstractInputDto;

import static modules.shared.application.Primitives.getInt;
import static modules.shared.application.Primitives.getTrimmedString;

public final class CreateTaskDto extends AbstractInputDto {

    private final int authUserId;
    private final String authUserUuid;
    private final String taskTitle;
    private final String taskStatus;
    private final String taskType;
    private final String targetDate;
    private final String taskDescription;

    private CreateTaskDto(Map<String, Object> primitives) {
        super(primitives);

        this.authUserId = getInt(primitives.get("authUserId"));
        this.authUserUuid = getTrimmedString(primitives.get("authUserUuid"));

        this.taskTitle = getTrimmedString(primitives.get("taskTitle"));
        this.taskStatus = getTrimmedString(primitives.get("taskStatus"));
        this.taskType = getTrimmedString(primitives.get("taskType"));
        this.targetDate = getTrimmedString(primitives.get("targetDate"));
        this.taskDescription = getTrimmedString(primitives.get("taskDescription"));
    }

    public static CreateTaskDto fromPrimitives(Map<String, Object> primitives) {
        return new CreateTaskDto(primitives);
    }

    public int getAuthUserId() {
        return authUserId;
    }

    public String getAuthUserUuid() {
        return authUserUuid;
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
