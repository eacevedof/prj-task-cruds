pub struct TasksMessageEnum;

impl TasksMessageEnum {
    pub const FIELD_IS_REQUIRED: &'static str = "field is required";
    pub const MAX_LENGTH_IS_X: &'static str = "max length is {0}";
    pub const ALLOWED_VALUES_ARE_X: &'static str = "allowed values are {0}";
    pub const INVALID_DATE_FORMAT_X: &'static str = "invalid date, expected format {0}";
    pub const TASK_X_NOT_FOUND: &'static str = "Task {0} not found";
    pub const USER_X_NOT_FOUND: &'static str = "User {0} not found";
    pub const MISSING_OR_INVALID_API_TOKEN: &'static str = "Missing or invalid API token";
    pub const MIN_VALUE_IS_X: &'static str = "min value is {0}";
    pub const VALUE_BETWEEN_X_AND_X: &'static str = "value must be between {0} and {1}";
    pub const DATE_TO_BEFORE_DATE_FROM: &'static str = "must be on or after target_date_from";
}
