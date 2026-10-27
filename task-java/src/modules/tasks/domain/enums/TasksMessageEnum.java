package modules.tasks.domain.enums;

public final class TasksMessageEnum {

    public static final String FIELD_IS_REQUIRED = "field is required";
    public static final String MAX_LENGTH_IS_X = "max length is {0}";
    public static final String ALLOWED_VALUES_ARE_X = "allowed values are {0}";
    public static final String INVALID_DATE_FORMAT_X = "invalid date, expected format {0}";
    public static final String TASK_X_NOT_FOUND = "Task {0} not found";
    public static final String USER_X_NOT_FOUND = "User {0} not found";
    public static final String MISSING_OR_INVALID_API_TOKEN = "Missing or invalid API token";
    public static final String MIN_VALUE_IS_X = "min value is {0}";
    public static final String VALUE_BETWEEN_X_AND_X = "value must be between {0} and {1}";
    public static final String DATE_TO_BEFORE_DATE_FROM = "must be on or after target_date_from";

    private TasksMessageEnum() {
    }
}
