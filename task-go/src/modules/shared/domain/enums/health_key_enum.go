package enums

// HealthKeyEnum respuesta de GET /health.
var HealthKeyEnum = struct {
	APP         string
	GO          string
	SQL_DRIVERS string
	APP_NAME    string
}{
	APP:         "app",
	GO:          "go",
	SQL_DRIVERS: "sql_drivers",
	APP_NAME:    "task-go",
}
