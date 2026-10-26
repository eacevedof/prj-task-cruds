/// claves de la API (JSON de entrada y salida, argumentos GraphQL) de tasks, mas las columnas internas con las que se
/// monta TaskEntity.
pub struct TasksKeyEnum;

impl TasksKeyEnum {
    pub const TASK_ID: &'static str = "task_id";
    pub const TASK_UUID: &'static str = "task_uuid";
    pub const USER_UUID: &'static str = "user_uuid";
    pub const TASK_TITLE: &'static str = "task_title";
    pub const TASK_STATUS: &'static str = "task_status";
    pub const TASK_TYPE: &'static str = "task_type";
    pub const TARGET_DATE: &'static str = "target_date";
    pub const TASK_DESCRIPTION: &'static str = "task_description";
    pub const CREATED_AT: &'static str = "created_at";
    pub const UPDATED_AT: &'static str = "updated_at";
    pub const AFFECTED: &'static str = "affected";

    // columnas internas de BD: solo para montar TaskEntity, nunca salen por la API
    pub const ID: &'static str = "id";
    pub const USER_ID: &'static str = "user_id";

    // busqueda paginada (GraphQL: argumentos de tasks/userTasks y tipo TaskPage)
    pub const FILTER: &'static str = "filter";
    pub const TASK_TEXT: &'static str = "task_text";
    pub const TARGET_DATE_FROM: &'static str = "target_date_from";
    pub const TARGET_DATE_TO: &'static str = "target_date_to";
    pub const PAGE: &'static str = "page";
    pub const PAGE_SIZE: &'static str = "page_size";
    pub const ITEMS: &'static str = "items";
    pub const TOTAL_ITEMS: &'static str = "total_items";
    pub const TOTAL_PAGES: &'static str = "total_pages";
}
