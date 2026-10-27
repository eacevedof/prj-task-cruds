/**
 * Valores de tasks.task_type (los mismos que el CHECK de la migracion create-tasks-table).
 */
export const TaskTypeEnum = Object.freeze({
    PERSONAL: "personal",
    WORK: "work",
    OTHER: "other",

    DEFAULT: "personal",

    VALUES: Object.freeze(["personal", "work", "other"]),
});
