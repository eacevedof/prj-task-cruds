package modules.tasks.application.deletetask;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.tasks.domain.enums.TasksKeyEnum;

import static modules.shared.application.Primitives.getInt;
import static modules.shared.application.Primitives.getTrimmedString;

public final class DeleteTaskResultDto {

    private final String taskUuid;
    private final int affected;

    private DeleteTaskResultDto(Map<String, Object> primitives) {
        this.taskUuid = getTrimmedString(primitives.get("taskUuid"));
        this.affected = getInt(primitives.get("affected"));
    }

    public static DeleteTaskResultDto fromPrimitives(Map<String, Object> primitives) {
        return new DeleteTaskResultDto(primitives);
    }

    public String getTaskUuid() {
        return taskUuid;
    }

    public int getAffected() {
        return affected;
    }

    public Map<String, Object> toArray() {
        return Primitives.of(
            TasksKeyEnum.TASK_UUID, taskUuid,
            TasksKeyEnum.AFFECTED, affected
        );
    }
}
