<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\LogInUserController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Users\Domain\Enums\UsersMessageEnum;
use Tests\AbstractTestCase;
use Tests\Modules\Shared\Infrastructure\Repositories\JsonResponseRepositoryMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;
use Tests\Modules\Users\Infrastructure\Controllers\LogInUserController\Mothers\{LogInUserControllerMother,
    LogInUserRequestMother,
    LogInUserTestCaseEnum};

final class LogInUserControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByValidCredentialsWithOtherCase(): void
    {
        $httpResponse = $this->getHttpResponseByTestCase(LogInUserTestCaseEnum::OK_AS_SUCCESS_BY_VALID_CREDENTIALS_WITH_OTHER_CASE);

        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
        $loggedInUser = JsonResponseRepositoryMock::fromHttpResponse($httpResponse)->getData();
        $this->assertEquals(UsersRawRepositoryMock::USER_UUID_TO_LOG_IN, $loggedInUser["user_uuid"]);
        $this->assertNotEmpty($loggedInUser["api_token"]);
        $this->assertArrayNotHasKey("password_hash", $loggedInUser);
    }

    public function testNokAsUnauthorizedByWrongPassword(): void
    {
        $this->assertUnauthorizedWithGenericMessage(LogInUserTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD);
    }

    public function testNokAsUnauthorizedByUnknownUserName(): void
    {
        $this->assertUnauthorizedWithGenericMessage(LogInUserTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_UNKNOWN_USER_NAME);
    }

    public function testNokAsUnauthorizedByUserWithoutPassword(): void
    {
        $this->assertUnauthorizedWithGenericMessage(LogInUserTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_USER_WITHOUT_PASSWORD);
    }

    /**
     * Los tres fallos responden igual: no se revela si el user_name existe.
     */
    private function assertUnauthorizedWithGenericMessage(LogInUserTestCaseEnum $useCase): void
    {
        $httpResponse = $this->getHttpResponseByTestCase($useCase);

        $this->assertEquals(ExceptionEnum::CODE_UNAUTHORIZED, $httpResponse->getStatusCode());
        $this->assertEquals(
            [UsersMessageEnum::INVALID_USER_NAME_OR_PASSWORD],
            JsonResponseRepositoryMock::fromHttpResponse($httpResponse)->getErrors()
        );
    }

    private function getHttpResponseByTestCase(LogInUserTestCaseEnum $useCase): HttpResponder
    {
        $routeArgs = LogInUserRequestMother::getInstance()->getRequestByTestCase($useCase);
        return LogInUserControllerMother::getControllerInstance()->logIn(...$routeArgs);
    }
}
