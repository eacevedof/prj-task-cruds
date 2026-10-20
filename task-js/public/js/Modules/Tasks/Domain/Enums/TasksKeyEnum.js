/**
 * Claves de la API de tasks (JSON de entrada y salida).
 */
export const TasksKeyEnum = Object.freeze({
    TASK_ID: "task_id",
    TASK_UUID: "task_uuid",
    USER_UUID: "user_uuid",
    TASK_TITLE: "task_title",
    TASK_STATUS: "task_status",
    TASK_TYPE: "task_type",
    TARGET_DATE: "target_date",
    TASK_DESCRIPTION: "task_description",
    CREATED_AT: "created_at",
    UPDATED_AT: "updated_at",

    // busqueda paginada (GraphQL: TasksFilter y TaskPage)
    TASK_TEXT: "task_text",
    TARGET_DATE_FROM: "target_date_from",
    TARGET_DATE_TO: "target_date_to",
    ITEMS: "items",
    PAGE: "page",
    PAGE_SIZE: "page_size",
    TOTAL_ITEMS: "total_items",
    TOTAL_PAGES: "total_pages",
});
