package modules.tasks.domain.enums;

import java.util.List;

/**
 * Valores de tasks.task_status (los mismos que el CHECK de la migracion create-tasks-table).
 */
public final class TaskStatusEnum {

    public static final String PENDING = "pending";
    public static final String IN_PROGRESS = "in_progress";
    public static final String DONE = "done";

    public static final String DEFAULT = PENDING;

    public static final List<String> VALUES = List.of(PENDING, IN_PROGRESS, DONE);

    private TaskStatusEnum() {
    }
}
