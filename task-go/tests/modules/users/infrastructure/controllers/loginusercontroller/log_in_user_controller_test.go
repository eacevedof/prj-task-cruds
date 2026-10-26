package loginusercontroller

import (
	"os"
	"testing"

	"task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
	usersenums "task-go/src/modules/users/domain/enums"
	"task-go/tests"
	"task-go/tests/modules/shared/infrastructure/repositories"
	"task-go/tests/modules/users/infrastructure/controllers/loginusercontroller/mothers"
)

func TestMain(m *testing.M) {
	os.Exit(tests.RunWithTestDb(m, "log-in"))
}

func TestOkAsSuccessByValidCredentialsWithOtherCase(t *testing.T) {
	httpResponse := getHttpResponseByTestCase(mothers.OK_AS_SUCCESS_BY_VALID_CREDENTIALS_WITH_OTHER_CASE)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_OK, httpResponse.GetStatusCode())
	loggedInUser := repositories.JsonResponseRepositoryMockFromHttpResponse(httpResponse).GetData()
	tests.AssertEquals(t, any(repositories.UsersRawRepositoryMock.USER_UUID_TO_LOG_IN), loggedInUser["user_uuid"])
	tests.AssertNotEmpty(t, loggedInUser["api_token"])
	_, hasPasswordHash := loggedInUser["password_hash"]
	tests.AssertEquals(t, false, hasPasswordHash)
}

func TestNokAsUnauthorizedByWrongPassword(t *testing.T) {
	assertUnauthorizedWithGenericMessage(t, mothers.NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD)
}

func TestNokAsUnauthorizedByUnknownUserName(t *testing.T) {
	assertUnauthorizedWithGenericMessage(t, mothers.NOK_AS_UNAUTHORIZED_BY_UNKNOWN_USER_NAME)
}

func TestNokAsUnauthorizedByUserWithoutPassword(t *testing.T) {
	assertUnauthorizedWithGenericMessage(t, mothers.NOK_AS_UNAUTHORIZED_BY_USER_WITHOUT_PASSWORD)
}

// assertUnauthorizedWithGenericMessage los tres fallos responden igual: no se revela si el user_name existe.
func assertUnauthorizedWithGenericMessage(t *testing.T, useCase mothers.LogInUserTestCaseEnum) {
	t.Helper()
	httpResponse := getHttpResponseByTestCase(useCase)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_UNAUTHORIZED, httpResponse.GetStatusCode())
	tests.AssertEquals(t,
		[]string{usersenums.UsersMessageEnum.INVALID_USER_NAME_OR_PASSWORD},
		repositories.JsonResponseRepositoryMockFromHttpResponse(httpResponse).GetErrors(),
	)
}

func getHttpResponseByTestCase(useCase mothers.LogInUserTestCaseEnum) *httpresponder.HttpResponder {
	httpRequester := mothers.LogInUserRequestMotherGetInstance().GetRequestByTestCase(useCase)
	return mothers.GetControllerInstance(httpRequester).LogIn()
}
