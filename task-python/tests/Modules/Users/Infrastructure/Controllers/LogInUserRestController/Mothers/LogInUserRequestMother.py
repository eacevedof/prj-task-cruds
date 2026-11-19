from tests.Modules.Shared.Infrastructure.Components.HttpRequestMock import HttpRequestMock
from tests.Modules.Shared.Infrastructure.Repositories.UsersRawRepositoryMock import UsersRawRepositoryMock
from tests.Modules.Users.Infrastructure.Controllers.LogInUserRestController.Mothers.LogInUserTestCaseEnum import LogInUserTestCaseEnum


class LogInUserRequestMother:

    @staticmethod
    def getInstance() -> "LogInUserRequestMother":
        return LogInUserRequestMother()

    def getRequestByTestCase(self, useCase: str) -> dict:
        """
        @returns argumentos de LogInUserRestController.invoke() (ninguno: todo va en el body)
        """
        match useCase:
            # user_name no distingue mayusculas
            case LogInUserTestCaseEnum.OK_AS_SUCCESS_BY_VALID_CREDENTIALS_WITH_OTHER_CASE:
                return HttpRequestMock.getInstance() \
                    .setPostRawParams({
                        "user_name": UsersRawRepositoryMock.USER_NAME_TO_LOG_IN.upper(),
                        "password": UsersRawRepositoryMock.USER_PASSWORD_TO_LOG_IN,
                    }) \
                    .getMockedInstance()

            case LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD:
                return HttpRequestMock.getInstance() \
                    .setPostRawParams({
                        "user_name": UsersRawRepositoryMock.USER_NAME_TO_LOG_IN,
                        "password": UsersRawRepositoryMock.USER_PASSWORD_NOK,
                    }) \
                    .getMockedInstance()

            case LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_UNKNOWN_USER_NAME:
                return HttpRequestMock.getInstance() \
                    .setPostRawParams({
                        "user_name": UsersRawRepositoryMock.USER_NAME_NOK,
                        "password": UsersRawRepositoryMock.USER_PASSWORD_TO_LOG_IN,
                    }) \
                    .getMockedInstance()

            # cuenta sin password (como las creadas antes de add-password-hash-to-users): no puede entrar
            case LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_USER_WITHOUT_PASSWORD:
                return HttpRequestMock.getInstance() \
                    .setPostRawParams({
                        "user_name": UsersRawRepositoryMock.USER_NAME_OK,
                        "password": UsersRawRepositoryMock.USER_PASSWORD_TO_LOG_IN,
                    }) \
                    .getMockedInstance()
        return {}
