package modules.tasks.application.gettask;

import java.util.Map;

import static modules.shared.application.Primitives.getPrimitives;

public final class GetTaskResultDto {

    // la task (TaskEntity.toPrimitives)
    private final Map<String, Object> task;

    private GetTaskResultDto(Map<String, Object> primitives) {
        this.task = getPrimitives(primitives.get("task"));
    }

    public static GetTaskResultDto fromPrimitives(Map<String, Object> primitives) {
        return new GetTaskResultDto(primitives);
    }

    public Map<String, Object> getTask() {
        return task;
    }

    public Map<String, Object> toArray() {
        return task;
    }
}
