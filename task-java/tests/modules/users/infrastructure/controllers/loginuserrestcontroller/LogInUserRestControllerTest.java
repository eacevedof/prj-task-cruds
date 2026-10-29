package modules.users.infrastructure.controllers.loginuserrestcontroller;

import java.util.List;
import java.util.Map;

import org.junit.jupiter.api.BeforeAll;
import org.junit.jupiter.api.Test;

import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;
import modules.shared.infrastructure.repositories.JsonResponseRepositoryMock;
import modules.shared.infrastructure.repositories.UsersRawRepositoryMock;
import modules.users.domain.enums.UsersMessageEnum;
import modules.users.infrastructure.controllers.loginuserrestcontroller.mothers.LogInUserRestControllerMother;
import modules.users.infrastructure.controllers.loginuserrestcontroller.mothers.LogInUserRequestMother;
import modules.users.infrastructure.controllers.loginuserrestcontroller.mothers.LogInUserTestCaseEnum;

import testcase.AbstractTestCase;
import testcase.Bootstrap;

class LogInUserRestControllerTest extends AbstractTestCase {

    @BeforeAll
    static void setUpTestDb() {
        Bootstrap.runWithTestDb("log-in");
    }

    @Test
    void testOkAsSuccessByValidCredentialsWithOtherCase() {
        HttpResponder httpResponse = getHttpResponseByTestCase(LogInUserTestCaseEnum.OK_AS_SUCCESS_BY_VALID_CREDENTIALS_WITH_OTHER_CASE);

        assertEquals(ExceptionEnum.CODE_OK, httpResponse.getStatusCode());
        Map<String, Object> loggedInUser = JsonResponseRepositoryMock.fromHttpResponse(httpResponse).getData();
        assertEquals(UsersRawRepositoryMock.USER_UUID_TO_LOG_IN, loggedInUser.get("user_uuid"));
        assertNotEmpty(loggedInUser.get("api_token"));
        assertArrayNotHasKey("password_hash", loggedInUser);
    }

    @Test
    void testNokAsUnauthorizedByWrongPassword() {
        assertUnauthorizedWithGenericMessage(LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD);
    }

    @Test
    void testNokAsUnauthorizedByUnknownUserName() {
        assertUnauthorizedWithGenericMessage(LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_UNKNOWN_USER_NAME);
    }

    @Test
    void testNokAsUnauthorizedByUserWithoutPassword() {
        assertUnauthorizedWithGenericMessage(LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_USER_WITHOUT_PASSWORD);
    }

    /**
     * Los tres fallos responden igual: no se revela si el user_name existe.
     */
    private void assertUnauthorizedWithGenericMessage(String useCase) {
        HttpResponder httpResponse = getHttpResponseByTestCase(useCase);

        assertEquals(ExceptionEnum.CODE_UNAUTHORIZED, httpResponse.getStatusCode());
        assertEquals(
            List.of(UsersMessageEnum.INVALID_USER_NAME_OR_PASSWORD),
            JsonResponseRepositoryMock.fromHttpResponse(httpResponse).getErrors()
        );
    }

    private HttpResponder getHttpResponseByTestCase(String useCase) {
        LogInUserRequestMother.getInstance().getRequestByTestCase(useCase);
        return LogInUserRestControllerMother.getControllerInstance().logIn();
    }
}
