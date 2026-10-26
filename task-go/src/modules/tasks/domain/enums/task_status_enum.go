package enums

// TaskStatusEnum valores de tasks.task_status (los mismos que el CHECK de la migracion create-tasks-table).
var TaskStatusEnum = struct {
	PENDING     string
	IN_PROGRESS string
	DONE        string
	DEFAULT     string
	VALUES      []string
}{
	PENDING:     "pending",
	IN_PROGRESS: "in_progress",
	DONE:        "done",
	DEFAULT:     "pending",
	VALUES:      []string{"pending", "in_progress", "done"},
}
