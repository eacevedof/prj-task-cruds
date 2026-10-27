package modules.users.infrastructure.controllers.loginusercontroller.mothers;

import java.util.Locale;
import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.components.HttpRequestMock;
import modules.shared.infrastructure.repositories.UsersRawRepositoryMock;

public final class LogInUserRequestMother {

    public static LogInUserRequestMother getInstance() {
        return new LogInUserRequestMother();
    }

    /**
     * @return argumentos de LogInUserController.logIn() (ninguno: todo va en el body)
     */
    public Map<String, String> getRequestByTestCase(String useCase) {
        return switch (useCase) {
            // user_name no distingue mayusculas
            case LogInUserTestCaseEnum.OK_AS_SUCCESS_BY_VALID_CREDENTIALS_WITH_OTHER_CASE -> HttpRequestMock.getInstance()
                .setPostRawParams(Primitives.of(
                    "user_name", UsersRawRepositoryMock.USER_NAME_TO_LOG_IN.toUpperCase(Locale.ROOT),
                    "password", UsersRawRepositoryMock.USER_PASSWORD_TO_LOG_IN
                ))
                .getMockedInstance();

            case LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD -> HttpRequestMock.getInstance()
                .setPostRawParams(Primitives.of(
                    "user_name", UsersRawRepositoryMock.USER_NAME_TO_LOG_IN,
                    "password", UsersRawRepositoryMock.USER_PASSWORD_NOK
                ))
                .getMockedInstance();

            case LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_UNKNOWN_USER_NAME -> HttpRequestMock.getInstance()
                .setPostRawParams(Primitives.of(
                    "user_name", UsersRawRepositoryMock.USER_NAME_NOK,
                    "password", UsersRawRepositoryMock.USER_PASSWORD_TO_LOG_IN
                ))
                .getMockedInstance();

            // cuenta sin password (como las creadas antes de add-password-hash-to-users): no puede entrar
            case LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_USER_WITHOUT_PASSWORD -> HttpRequestMock.getInstance()
                .setPostRawParams(Primitives.of(
                    "user_name", UsersRawRepositoryMock.USER_NAME_OK,
                    "password", UsersRawRepositoryMock.USER_PASSWORD_TO_LOG_IN
                ))
                .getMockedInstance();

            default -> throw new IllegalArgumentException("unknown test case " + useCase);
        };
    }
}
