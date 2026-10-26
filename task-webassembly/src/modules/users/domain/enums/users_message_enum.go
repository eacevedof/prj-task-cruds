package enums

// UsersMessageEnum errores (en ingles, igual que la API: no se traducen).
var UsersMessageEnum = struct {
	API_TOKEN_IS_REQUIRED  string
	USER_NAME_IS_REQUIRED  string
	USER_EMAIL_IS_REQUIRED string
	PASSWORD_IS_REQUIRED   string
	UNEXPECTED_ERROR       string
}{
	API_TOKEN_IS_REQUIRED:  "api_token: field is required",
	USER_NAME_IS_REQUIRED:  "user_name: field is required",
	USER_EMAIL_IS_REQUIRED: "user_email: field is required",
	PASSWORD_IS_REQUIRED:   "password: field is required",
	UNEXPECTED_ERROR:       "An unexpected error has occurred",
}
