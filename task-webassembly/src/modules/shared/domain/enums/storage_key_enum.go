package enums

// StorageKeyEnum claves de localStorage (preferencias del navegador que sobreviven a recargar).
var StorageKeyEnum = struct {
	LANGUAGE_CODE string
	BACKEND_CODE  string
	TASKS_FILTERS string
}{
	LANGUAGE_CODE: "task-webassembly-language-code",
	BACKEND_CODE:  "task-webassembly-backend-code",
	TASKS_FILTERS: "task-webassembly-tasks-filters",
}
