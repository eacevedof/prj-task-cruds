package modules.tasks.application.createtask;

import java.util.Map;

import static modules.shared.application.Primitives.getPrimitives;

public final class CreateTaskResultDto {

    // la task creada (TaskEntity.toPrimitives)
    private final Map<String, Object> task;

    private CreateTaskResultDto(Map<String, Object> primitives) {
        this.task = getPrimitives(primitives.get("task"));
    }

    public static CreateTaskResultDto fromPrimitives(Map<String, Object> primitives) {
        return new CreateTaskResultDto(primitives);
    }

    public Map<String, Object> getTask() {
        return task;
    }

    public Map<String, Object> toArray() {
        return task;
    }
}
