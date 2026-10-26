package enums

var TasksMessageEnum = struct {
	FIELD_IS_REQUIRED            string
	MAX_LENGTH_IS_X              string
	ALLOWED_VALUES_ARE_X         string
	INVALID_DATE_FORMAT_X        string
	TASK_X_NOT_FOUND             string
	USER_X_NOT_FOUND             string
	MISSING_OR_INVALID_API_TOKEN string
	MIN_VALUE_IS_X               string
	VALUE_BETWEEN_X_AND_X        string
	DATE_TO_BEFORE_DATE_FROM     string
}{
	FIELD_IS_REQUIRED:            "field is required",
	MAX_LENGTH_IS_X:              "max length is {0}",
	ALLOWED_VALUES_ARE_X:         "allowed values are {0}",
	INVALID_DATE_FORMAT_X:        "invalid date, expected format {0}",
	TASK_X_NOT_FOUND:             "Task {0} not found",
	USER_X_NOT_FOUND:             "User {0} not found",
	MISSING_OR_INVALID_API_TOKEN: "Missing or invalid API token",
	MIN_VALUE_IS_X:               "min value is {0}",
	VALUE_BETWEEN_X_AND_X:        "value must be between {0} and {1}",
	DATE_TO_BEFORE_DATE_FROM:     "must be on or after target_date_from",
}
