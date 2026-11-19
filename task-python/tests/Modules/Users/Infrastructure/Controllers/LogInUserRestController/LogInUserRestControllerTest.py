from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum
from Modules.Users.Domain.Enums.UsersMessageEnum import UsersMessageEnum

from tests.AbstractTestCase import AbstractTestCase
from tests.bootstrap import runWithTestDb
from tests.Modules.Shared.Infrastructure.Repositories.JsonResponseRepositoryMock import JsonResponseRepositoryMock
from tests.Modules.Shared.Infrastructure.Repositories.UsersRawRepositoryMock import UsersRawRepositoryMock
from tests.Modules.Users.Infrastructure.Controllers.LogInUserRestController.Mothers.LogInUserRestControllerMother import LogInUserRestControllerMother
from tests.Modules.Users.Infrastructure.Controllers.LogInUserRestController.Mothers.LogInUserRequestMother import LogInUserRequestMother
from tests.Modules.Users.Infrastructure.Controllers.LogInUserRestController.Mothers.LogInUserTestCaseEnum import LogInUserTestCaseEnum


class LogInUserRestControllerTest(AbstractTestCase):

    @classmethod
    def setUpClass(cls) -> None:
        runWithTestDb("log-in")

    def testOkAsSuccessByValidCredentialsWithOtherCase(self) -> None:
        httpResponse = self._getHttpResponseByTestCase(LogInUserTestCaseEnum.OK_AS_SUCCESS_BY_VALID_CREDENTIALS_WITH_OTHER_CASE)

        self.assertEquals(ExceptionEnum.CODE_OK, httpResponse.getStatusCode())
        loggedInUser = JsonResponseRepositoryMock.fromHttpResponse(httpResponse).getData()
        self.assertEquals(UsersRawRepositoryMock.USER_UUID_TO_LOG_IN, loggedInUser["user_uuid"])
        self.assertNotEmpty(loggedInUser["api_token"])
        self.assertArrayNotHasKey("password_hash", loggedInUser)

    def testNokAsUnauthorizedByWrongPassword(self) -> None:
        self._assertUnauthorizedWithGenericMessage(LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD)

    def testNokAsUnauthorizedByUnknownUserName(self) -> None:
        self._assertUnauthorizedWithGenericMessage(LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_UNKNOWN_USER_NAME)

    def testNokAsUnauthorizedByUserWithoutPassword(self) -> None:
        self._assertUnauthorizedWithGenericMessage(LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_USER_WITHOUT_PASSWORD)

    def _assertUnauthorizedWithGenericMessage(self, useCase: str) -> None:
        """
        Los tres fallos responden igual: no se revela si el user_name existe.
        """
        httpResponse = self._getHttpResponseByTestCase(useCase)

        self.assertEquals(ExceptionEnum.CODE_UNAUTHORIZED, httpResponse.getStatusCode())
        self.assertEquals(
            [UsersMessageEnum.INVALID_USER_NAME_OR_PASSWORD],
            JsonResponseRepositoryMock.fromHttpResponse(httpResponse).getErrors()
        )

    def _getHttpResponseByTestCase(self, useCase: str):
        LogInUserRequestMother.getInstance().getRequestByTestCase(useCase)
        return LogInUserRestControllerMother.getControllerInstance().invoke()
