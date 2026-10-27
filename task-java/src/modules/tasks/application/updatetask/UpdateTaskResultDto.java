package modules.tasks.application.updatetask;

import java.util.Map;

import static modules.shared.application.Primitives.getPrimitives;

public final class UpdateTaskResultDto {

    // la task modificada (TaskEntity.toPrimitives)
    private final Map<String, Object> task;

    private UpdateTaskResultDto(Map<String, Object> primitives) {
        this.task = getPrimitives(primitives.get("task"));
    }

    public static UpdateTaskResultDto fromPrimitives(Map<String, Object> primitives) {
        return new UpdateTaskResultDto(primitives);
    }

    public Map<String, Object> getTask() {
        return task;
    }

    public Map<String, Object> toArray() {
        return task;
    }
}
