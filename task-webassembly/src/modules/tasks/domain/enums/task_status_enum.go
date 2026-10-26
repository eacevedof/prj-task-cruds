package enums

// TaskStatusEnum los mismos valores que la API (task-php TaskStatusEnum). La etiqueta la pone el Translator:
// tasks.status.<valor>
var TaskStatusEnum = struct {
	PENDING     string
	IN_PROGRESS string
	DONE        string

	VALUES []string
}{
	PENDING:     "pending",
	IN_PROGRESS: "in_progress",
	DONE:        "done",

	VALUES: []string{"pending", "in_progress", "done"},
}
