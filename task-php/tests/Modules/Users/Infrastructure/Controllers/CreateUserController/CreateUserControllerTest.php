<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\CreateUserController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use Tests\AbstractTestCase;
use Tests\Modules\Shared\Infrastructure\Repositories\JsonResponseRepositoryMock;
use Tests\Modules\Users\Infrastructure\Controllers\CreateUserController\Mothers\{CreateUserControllerMother,
    CreateUserRequestMother,
    CreateUserTestCaseEnum};

final class CreateUserControllerTest extends AbstractTestCase
{
    public function testOkAsCreatedByValidFieldsWithoutToken(): void
    {
        $routeArgs = CreateUserRequestMother::getInstance()
            ->getRequestByTestCase(CreateUserTestCaseEnum::OK_AS_CREATED_BY_VALID_FIELDS_WITHOUT_TOKEN);

        $httpResponse = CreateUserControllerMother::getControllerInstance()->create(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_CREATED, $httpResponse->getStatusCode());
    }

    public function testNokAsBadRequestByEmptyFields(): void
    {
        $routeArgs = CreateUserRequestMother::getInstance()
            ->getRequestByTestCase(CreateUserTestCaseEnum::NOK_AS_BAD_REQUEST_BY_EMPTY_FIELDS);

        $httpResponse = CreateUserControllerMother::getControllerInstance()->create(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $httpResponse->getStatusCode());
    }

    public function testNokAsBadRequestByInvalidEmail(): void
    {
        $routeArgs = CreateUserRequestMother::getInstance()
            ->getRequestByTestCase(CreateUserTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_EMAIL);

        $httpResponse = CreateUserControllerMother::getControllerInstance()->create(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $httpResponse->getStatusCode());
    }

    public function testNokAsConflictByExistingEmail(): void
    {
        $routeArgs = CreateUserRequestMother::getInstance()
            ->getRequestByTestCase(CreateUserTestCaseEnum::NOK_AS_CONFLICT_BY_EXISTING_EMAIL);

        $httpResponse = CreateUserControllerMother::getControllerInstance()->create(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_CONFLICT, $httpResponse->getStatusCode());
    }

    public function testNokAsConflictByExistingUserName(): void
    {
        $routeArgs = CreateUserRequestMother::getInstance()
            ->getRequestByTestCase(CreateUserTestCaseEnum::NOK_AS_CONFLICT_BY_EXISTING_USER_NAME);

        $httpResponse = CreateUserControllerMother::getControllerInstance()->create(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_CONFLICT, $httpResponse->getStatusCode());
    }

    public function testNokAsBadRequestByInvalidUserNameAndShortPassword(): void
    {
        $routeArgs = CreateUserRequestMother::getInstance()
            ->getRequestByTestCase(CreateUserTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_USER_NAME_AND_SHORT_PASSWORD);

        $httpResponse = CreateUserControllerMother::getControllerInstance()->create(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $httpResponse->getStatusCode());
        // los dos campos erroneos viajan en el mensaje de la FieldsException
        $errors = JsonResponseRepositoryMock::fromHttpResponse($httpResponse)->getErrors();
        $this->assertStringContainsString("user_name:", $errors[0]);
        $this->assertStringContainsString("password:", $errors[0]);
    }
}
