pub struct UsersMessageEnum;

impl UsersMessageEnum {
    pub const FIELD_IS_REQUIRED: &'static str = "field is required";
    pub const MAX_LENGTH_IS_X: &'static str = "max length is {0}";
    pub const MIN_LENGTH_IS_X: &'static str = "min length is {0}";
    pub const ONLY_LETTERS_DIGITS_DOT_UNDERSCORE_HYPHEN: &'static str =
        "only letters, digits, dot, underscore and hyphen";
    pub const INVALID_EMAIL: &'static str = "invalid email";
    pub const USER_X_NOT_FOUND: &'static str = "User {0} not found";
    pub const EMAIL_X_ALREADY_EXISTS: &'static str = "Email {0} already exists";
    pub const USER_NAME_X_ALREADY_EXISTS: &'static str = "User name {0} already exists";
    pub const INVALID_USER_NAME_OR_PASSWORD: &'static str = "Invalid user name or password";
    pub const MISSING_OR_INVALID_API_TOKEN: &'static str = "Missing or invalid API token";
}
