package enums

// TaskTypeEnum valores de tasks.task_type (los mismos que el CHECK de la migracion create-tasks-table).
var TaskTypeEnum = struct {
	PERSONAL string
	WORK     string
	OTHER    string
	DEFAULT  string
	VALUES   []string
}{
	PERSONAL: "personal",
	WORK:     "work",
	OTHER:    "other",
	DEFAULT:  "personal",
	VALUES:   []string{"personal", "work", "other"},
}
