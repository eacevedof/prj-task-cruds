package enums

// BackendCodeEnum backs que puede consumir la SPA (los ofrece /config.json): el valor es el code de cada uno en la config.
var BackendCodeEnum = struct {
	PHP    string
	GO     string
	RUST   string
	NODEJS string
	JAVA   string
	PYTHON string

	DEFAULT string
}{
	PHP:    "php",
	GO:     "go",
	RUST:   "rust",
	NODEJS: "nodejs",
	JAVA:   "java",
	PYTHON: "python",

	DEFAULT: "php",
}
