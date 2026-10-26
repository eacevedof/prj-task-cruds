package enums

// TasksTextEnum claves de los textos de la interfaz de tasks (Translator: TextsEn / TextsEs).
var TasksTextEnum = struct {
	CREATED           string
	UPDATED           string
	DELETED           string
	CONFIRM_DELETE    string
	FORM_TITLE_CREATE string
	FORM_TITLE_UPDATE string
	LIST_EMPTY        string
	LIST_NO_MATCHES   string
	EDIT              string
	DELETE            string
	PAGE_OF           string
	DETAIL_TITLE      string
	EMPTY_VALUE       string
	STATUS_PREFIX     string
	TYPE_PREFIX       string
}{
	CREATED:           "tasks.created",
	UPDATED:           "tasks.updated",
	DELETED:           "tasks.deleted",
	CONFIRM_DELETE:    "tasks.confirm_delete",
	FORM_TITLE_CREATE: "tasks.form.title_create",
	FORM_TITLE_UPDATE: "tasks.form.title_update",
	LIST_EMPTY:        "tasks.list.empty",
	LIST_NO_MATCHES:   "tasks.list.no_matches",
	EDIT:              "tasks.list.edit",
	DELETE:            "tasks.list.delete",
	PAGE_OF:           "tasks.pagination.page_of",
	DETAIL_TITLE:      "tasks.detail.title",
	EMPTY_VALUE:       "tasks.detail.empty_value",
	STATUS_PREFIX:     "tasks.status.",
	TYPE_PREFIX:       "tasks.type.",
}
