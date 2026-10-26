package enums

var UsersMessageEnum = struct {
	FIELD_IS_REQUIRED                         string
	MAX_LENGTH_IS_X                           string
	MIN_LENGTH_IS_X                           string
	ONLY_LETTERS_DIGITS_DOT_UNDERSCORE_HYPHEN string
	INVALID_EMAIL                             string
	USER_X_NOT_FOUND                          string
	EMAIL_X_ALREADY_EXISTS                    string
	USER_NAME_X_ALREADY_EXISTS                string
	INVALID_USER_NAME_OR_PASSWORD             string
	MISSING_OR_INVALID_API_TOKEN              string
}{
	FIELD_IS_REQUIRED: "field is required",
	MAX_LENGTH_IS_X:   "max length is {0}",
	MIN_LENGTH_IS_X:   "min length is {0}",
	ONLY_LETTERS_DIGITS_DOT_UNDERSCORE_HYPHEN: "only letters, digits, dot, underscore and hyphen",
	INVALID_EMAIL:                 "invalid email",
	USER_X_NOT_FOUND:              "User {0} not found",
	EMAIL_X_ALREADY_EXISTS:        "Email {0} already exists",
	USER_NAME_X_ALREADY_EXISTS:    "User name {0} already exists",
	INVALID_USER_NAME_OR_PASSWORD: "Invalid user name or password",
	MISSING_OR_INVALID_API_TOKEN:  "Missing or invalid API token",
}
