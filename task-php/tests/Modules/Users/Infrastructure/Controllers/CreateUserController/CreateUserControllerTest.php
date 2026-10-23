<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\CreateUserController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use Tests\AbstractTestCase;
use Tests\Modules\Users\Infrastructure\Controllers\CreateUserController\Mothers\{
    CreateUserTestCaseEnum,
    CreateUserRequestMother,
    CreateUserControllerMother
};

final class CreateUserControllerTest extends AbstractTestCase
{
    public function testOkAsCreatedByValidFields(): void
    {
        $routeArgs = CreateUserRequestMother::getInstance()
            ->getRequestByTestCase(CreateUserTestCaseEnum::OK_AS_CREATED_BY_VALID_FIELDS);

        $httpResponse = CreateUserControllerMother::getControllerInstance()->create(...$routeArgs);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_CREATED, $httpResponse->getStatusCode());
    }

    public function testNokAsBadRequestByEmptyFields(): void
    {
        $routeArgs = CreateUserRequestMother::getInstance()
            ->getRequestByTestCase(CreateUserTestCaseEnum::NOK_AS_BAD_REQUEST_BY_EMPTY_FIELDS);

        $httpResponse = CreateUserControllerMother::getControllerInstance()->create(...$routeArgs);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $httpResponse->getStatusCode());
    }

    public function testNokAsBadRequestByInvalidEmail(): void
    {
        $routeArgs = CreateUserRequestMother::getInstance()
            ->getRequestByTestCase(CreateUserTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_EMAIL);

        $httpResponse = CreateUserControllerMother::getControllerInstance()->create(...$routeArgs);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $httpResponse->getStatusCode());
    }

    public function testNokAsConflictByExistingEmail(): void
    {
        $routeArgs = CreateUserRequestMother::getInstance()
            ->getRequestByTestCase(CreateUserTestCaseEnum::NOK_AS_CONFLICT_BY_EXISTING_EMAIL);

        $httpResponse = CreateUserControllerMother::getControllerInstance()->create(...$routeArgs);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_CONFLICT, $httpResponse->getStatusCode());
    }

}
