/**
 * Valores de tasks.task_status (los mismos que el CHECK de la migracion create-tasks-table).
 */
export const TaskStatusEnum = Object.freeze({
    PENDING: "pending",
    IN_PROGRESS: "in_progress",
    DONE: "done",

    DEFAULT: "pending",

    VALUES: Object.freeze(["pending", "in_progress", "done"]),
});
