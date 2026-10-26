package enums

// TasksMessageEnum errores (en ingles, igual que la API: no se traducen).
var TasksMessageEnum = struct {
	TASK_TITLE_IS_REQUIRED  string
	TARGET_DATE_IS_REQUIRED string
	UNEXPECTED_ERROR        string
}{
	TASK_TITLE_IS_REQUIRED:  "task_title: field is required",
	TARGET_DATE_IS_REQUIRED: "target_date: field is required",
	UNEXPECTED_ERROR:        "An unexpected error has occurred",
}
