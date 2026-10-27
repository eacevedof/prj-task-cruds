import { HttpRequestMock } from "#tests/Modules/Shared/Infrastructure/Components/HttpRequestMock.ts";
import { UsersRawRepositoryMock } from "#tests/Modules/Shared/Infrastructure/Repositories/UsersRawRepositoryMock.ts";
import {
    LogInUserTestCaseEnum,
    type LogInUserTestCase,
} from "#tests/Modules/Users/Infrastructure/Controllers/LogInUserController/Mothers/LogInUserTestCaseEnum.ts";

export class LogInUserRequestMother {

    static getInstance(): LogInUserRequestMother {
        return new LogInUserRequestMother();
    }

    /**
     * @returns argumentos de LogInUserController.logIn() (ninguno: todo va en el body)
     */
    getRequestByTestCase(useCase: LogInUserTestCase): Record<string, string> {
        switch (useCase) {
            // user_name no distingue mayusculas
            case LogInUserTestCaseEnum.OK_AS_SUCCESS_BY_VALID_CREDENTIALS_WITH_OTHER_CASE:
                return HttpRequestMock.getInstance()
                    .setPostRawParams({
                        user_name: UsersRawRepositoryMock.USER_NAME_TO_LOG_IN.toUpperCase(),
                        password: UsersRawRepositoryMock.USER_PASSWORD_TO_LOG_IN,
                    })
                    .getMockedInstance();

            case LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD:
                return HttpRequestMock.getInstance()
                    .setPostRawParams({
                        user_name: UsersRawRepositoryMock.USER_NAME_TO_LOG_IN,
                        password: UsersRawRepositoryMock.USER_PASSWORD_NOK,
                    })
                    .getMockedInstance();

            case LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_UNKNOWN_USER_NAME:
                return HttpRequestMock.getInstance()
                    .setPostRawParams({
                        user_name: UsersRawRepositoryMock.USER_NAME_NOK,
                        password: UsersRawRepositoryMock.USER_PASSWORD_TO_LOG_IN,
                    })
                    .getMockedInstance();

            // cuenta sin password (como las creadas antes de add-password-hash-to-users): no puede entrar
            case LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_USER_WITHOUT_PASSWORD:
                return HttpRequestMock.getInstance()
                    .setPostRawParams({
                        user_name: UsersRawRepositoryMock.USER_NAME_OK,
                        password: UsersRawRepositoryMock.USER_PASSWORD_TO_LOG_IN,
                    })
                    .getMockedInstance();
        }
    }
}
