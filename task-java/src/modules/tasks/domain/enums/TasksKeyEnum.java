package modules.tasks.domain.enums;

/**
 * Claves de la API (JSON de entrada y salida, argumentos GraphQL) de tasks, mas las columnas internas
 * con las que se monta TaskEntity.
 */
public final class TasksKeyEnum {

    public static final String TASK_ID = "task_id";
    public static final String TASK_UUID = "task_uuid";
    public static final String USER_UUID = "user_uuid";
    public static final String TASK_TITLE = "task_title";
    public static final String TASK_STATUS = "task_status";
    public static final String TASK_TYPE = "task_type";
    public static final String TARGET_DATE = "target_date";
    public static final String TASK_DESCRIPTION = "task_description";
    public static final String CREATED_AT = "created_at";
    public static final String UPDATED_AT = "updated_at";
    public static final String AFFECTED = "affected";

    // columnas internas de BD: solo para montar TaskEntity, nunca salen por la API
    public static final String ID = "id";
    public static final String USER_ID = "user_id";

    // busqueda paginada (GraphQL: argumentos de tasks/userTasks y tipo TaskPage)
    public static final String FILTER = "filter";
    public static final String TASK_TEXT = "task_text";
    public static final String TARGET_DATE_FROM = "target_date_from";
    public static final String TARGET_DATE_TO = "target_date_to";
    public static final String PAGE = "page";
    public static final String PAGE_SIZE = "page_size";
    public static final String ITEMS = "items";
    public static final String TOTAL_ITEMS = "total_items";
    public static final String TOTAL_PAGES = "total_pages";

    private TasksKeyEnum() {
    }
}
