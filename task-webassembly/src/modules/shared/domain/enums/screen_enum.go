package enums

// ScreenEnum pantallas de la SPA: el valor es el hash de la url (#tasks, #account) y el data-screen de su section.
var ScreenEnum = struct {
	TASKS   string
	ACCOUNT string

	DEFAULT string
	VALUES  []string
}{
	TASKS:   "tasks",
	ACCOUNT: "account",

	DEFAULT: "tasks",
	VALUES:  []string{"tasks", "account"},
}
