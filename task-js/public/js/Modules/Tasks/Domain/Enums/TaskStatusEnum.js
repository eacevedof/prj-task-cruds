/**
 * Los mismos valores que la API (task-php TaskStatusEnum). La etiqueta la pone el Translator: tasks.status.<valor>
 */
export const TaskStatusEnum = Object.freeze({
    PENDING: "pending",
    IN_PROGRESS: "in_progress",
    DONE: "done",

    VALUES: Object.freeze(["pending", "in_progress", "done"]),
});
