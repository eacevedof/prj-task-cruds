package modules.users.infrastructure.controllers.loginuserrestcontroller.mothers;

public final class LogInUserTestCaseEnum {

    public static final String OK_AS_SUCCESS_BY_VALID_CREDENTIALS_WITH_OTHER_CASE = "OK_AS_SUCCESS_BY_VALID_CREDENTIALS_WITH_OTHER_CASE";
    public static final String NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD = "NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD";
    public static final String NOK_AS_UNAUTHORIZED_BY_UNKNOWN_USER_NAME = "NOK_AS_UNAUTHORIZED_BY_UNKNOWN_USER_NAME";
    public static final String NOK_AS_UNAUTHORIZED_BY_USER_WITHOUT_PASSWORD = "NOK_AS_UNAUTHORIZED_BY_USER_WITHOUT_PASSWORD";

    private LogInUserTestCaseEnum() {
    }
}
