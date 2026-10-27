package modules.tasks.domain.enums;

import java.util.List;

/**
 * Valores de tasks.task_type (los mismos que el CHECK de la migracion create-tasks-table).
 */
public final class TaskTypeEnum {

    public static final String PERSONAL = "personal";
    public static final String WORK = "work";
    public static final String OTHER = "other";

    public static final String DEFAULT = PERSONAL;

    public static final List<String> VALUES = List.of(PERSONAL, WORK, OTHER);

    private TaskTypeEnum() {
    }
}
