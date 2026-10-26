package enums

// AccountMessageEnum errores (en ingles, igual que la API: no se traducen).
var AccountMessageEnum = struct {
	USER_NAME_IS_REQUIRED  string
	USER_EMAIL_IS_REQUIRED string
	UNEXPECTED_ERROR       string
}{
	USER_NAME_IS_REQUIRED:  "user_name: field is required",
	USER_EMAIL_IS_REQUIRED: "user_email: field is required",
	UNEXPECTED_ERROR:       "An unexpected error has occurred",
}
