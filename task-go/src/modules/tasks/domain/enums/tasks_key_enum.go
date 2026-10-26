package enums

// TasksKeyEnum claves de la API (JSON de entrada y salida, argumentos GraphQL) de tasks, mas las columnas internas
// con las que se monta TaskEntity.
var TasksKeyEnum = struct {
	TASK_ID          string
	TASK_UUID        string
	USER_UUID        string
	TASK_TITLE       string
	TASK_STATUS      string
	TASK_TYPE        string
	TARGET_DATE      string
	TASK_DESCRIPTION string
	CREATED_AT       string
	UPDATED_AT       string
	AFFECTED         string

	// columnas internas de BD: solo para montar TaskEntity, nunca salen por la API
	ID      string
	USER_ID string

	// busqueda paginada (GraphQL: argumentos de tasks/userTasks y tipo TaskPage)
	FILTER           string
	TASK_TEXT        string
	TARGET_DATE_FROM string
	TARGET_DATE_TO   string
	PAGE             string
	PAGE_SIZE        string
	ITEMS            string
	TOTAL_ITEMS      string
	TOTAL_PAGES      string
}{
	TASK_ID:          "task_id",
	TASK_UUID:        "task_uuid",
	USER_UUID:        "user_uuid",
	TASK_TITLE:       "task_title",
	TASK_STATUS:      "task_status",
	TASK_TYPE:        "task_type",
	TARGET_DATE:      "target_date",
	TASK_DESCRIPTION: "task_description",
	CREATED_AT:       "created_at",
	UPDATED_AT:       "updated_at",
	AFFECTED:         "affected",

	ID:      "id",
	USER_ID: "user_id",

	FILTER:           "filter",
	TASK_TEXT:        "task_text",
	TARGET_DATE_FROM: "target_date_from",
	TARGET_DATE_TO:   "target_date_to",
	PAGE:             "page",
	PAGE_SIZE:        "page_size",
	ITEMS:            "items",
	TOTAL_ITEMS:      "total_items",
	TOTAL_PAGES:      "total_pages",
}
