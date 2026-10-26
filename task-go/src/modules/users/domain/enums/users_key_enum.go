package enums

// UsersKeyEnum claves de la API (JSON de entrada y salida, argumentos GraphQL) de users, mas las columnas internas
// con las que se monta UserEntity.
var UsersKeyEnum = struct {
	USER_UUID  string
	USER_NAME  string
	USER_EMAIL string
	CREATED_AT string
	UPDATED_AT string
	API_TOKEN  string
	PASSWORD   string
	AFFECTED   string

	// columnas internas de BD: solo para montar UserEntity, nunca salen por la API
	ID             string
	PASSWORD_HASH  string
	API_TOKEN_HASH string
}{
	USER_UUID:  "user_uuid",
	USER_NAME:  "user_name",
	USER_EMAIL: "user_email",
	CREATED_AT: "created_at",
	UPDATED_AT: "updated_at",
	API_TOKEN:  "api_token",
	PASSWORD:   "password",
	AFFECTED:   "affected",

	ID:             "id",
	PASSWORD_HASH:  "password_hash",
	API_TOKEN_HASH: "api_token_hash",
}
