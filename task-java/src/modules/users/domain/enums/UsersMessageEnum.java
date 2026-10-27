package modules.users.domain.enums;

public final class UsersMessageEnum {

    public static final String FIELD_IS_REQUIRED = "field is required";
    public static final String MAX_LENGTH_IS_X = "max length is {0}";
    public static final String MIN_LENGTH_IS_X = "min length is {0}";
    public static final String ONLY_LETTERS_DIGITS_DOT_UNDERSCORE_HYPHEN = "only letters, digits, dot, underscore and hyphen";
    public static final String INVALID_EMAIL = "invalid email";
    public static final String USER_X_NOT_FOUND = "User {0} not found";
    public static final String EMAIL_X_ALREADY_EXISTS = "Email {0} already exists";
    public static final String USER_NAME_X_ALREADY_EXISTS = "User name {0} already exists";
    public static final String INVALID_USER_NAME_OR_PASSWORD = "Invalid user name or password";
    public static final String MISSING_OR_INVALID_API_TOKEN = "Missing or invalid API token";

    private UsersMessageEnum() {
    }
}
