package enums

// UsersKeyEnum claves de la API de users (JSON de entrada y salida).
var UsersKeyEnum = struct {
	USER_UUID  string
	USER_NAME  string
	USER_EMAIL string
	API_TOKEN  string
	PASSWORD   string
}{
	USER_UUID:  "user_uuid",
	USER_NAME:  "user_name",
	USER_EMAIL: "user_email",
	API_TOKEN:  "api_token",
	PASSWORD:   "password",
}
