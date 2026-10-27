import { runWithTestDb } from "#tests/bootstrap.ts";

import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import type { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";
import { UsersMessageEnum } from "#app/Modules/Users/Domain/Enums/UsersMessageEnum.ts";

import { AbstractTestCase } from "#tests/AbstractTestCase.ts";
import { JsonResponseRepositoryMock } from "#tests/Modules/Shared/Infrastructure/Repositories/JsonResponseRepositoryMock.ts";
import { UsersRawRepositoryMock } from "#tests/Modules/Shared/Infrastructure/Repositories/UsersRawRepositoryMock.ts";
import { LogInUserControllerMother } from "#tests/Modules/Users/Infrastructure/Controllers/LogInUserController/Mothers/LogInUserControllerMother.ts";
import { LogInUserRequestMother } from "#tests/Modules/Users/Infrastructure/Controllers/LogInUserController/Mothers/LogInUserRequestMother.ts";
import {
    LogInUserTestCaseEnum,
    type LogInUserTestCase,
} from "#tests/Modules/Users/Infrastructure/Controllers/LogInUserController/Mothers/LogInUserTestCaseEnum.ts";

runWithTestDb("log-in");

class LogInUserControllerTest extends AbstractTestCase {

    testOkAsSuccessByValidCredentialsWithOtherCase(): void {
        const httpResponse = this.#getHttpResponseByTestCase(LogInUserTestCaseEnum.OK_AS_SUCCESS_BY_VALID_CREDENTIALS_WITH_OTHER_CASE);

        this.assertEquals(ExceptionEnum.CODE_OK, httpResponse.getStatusCode());
        const loggedInUser = JsonResponseRepositoryMock.fromHttpResponse(httpResponse).getData();
        this.assertEquals(UsersRawRepositoryMock.USER_UUID_TO_LOG_IN, loggedInUser.user_uuid);
        this.assertNotEmpty(loggedInUser.api_token);
        this.assertArrayNotHasKey("password_hash", loggedInUser);
    }

    testNokAsUnauthorizedByWrongPassword(): void {
        this.#assertUnauthorizedWithGenericMessage(LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD);
    }

    testNokAsUnauthorizedByUnknownUserName(): void {
        this.#assertUnauthorizedWithGenericMessage(LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_UNKNOWN_USER_NAME);
    }

    testNokAsUnauthorizedByUserWithoutPassword(): void {
        this.#assertUnauthorizedWithGenericMessage(LogInUserTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_USER_WITHOUT_PASSWORD);
    }

    /**
     * Los tres fallos responden igual: no se revela si el user_name existe.
     */
    #assertUnauthorizedWithGenericMessage(useCase: LogInUserTestCase): void {
        const httpResponse = this.#getHttpResponseByTestCase(useCase);

        this.assertEquals(ExceptionEnum.CODE_UNAUTHORIZED, httpResponse.getStatusCode());
        this.assertEquals(
            [UsersMessageEnum.INVALID_USER_NAME_OR_PASSWORD],
            JsonResponseRepositoryMock.fromHttpResponse(httpResponse).getErrors()
        );
    }

    #getHttpResponseByTestCase(useCase: LogInUserTestCase): HttpResponder {
        LogInUserRequestMother.getInstance().getRequestByTestCase(useCase);
        return LogInUserControllerMother.getControllerInstance().logIn();
    }
}

AbstractTestCase.runTests(LogInUserControllerTest);
