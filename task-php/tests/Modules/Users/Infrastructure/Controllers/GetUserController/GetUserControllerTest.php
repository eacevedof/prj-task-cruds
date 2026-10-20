<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\GetUserController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use Tests\AbstractTestCase;
use Tests\Modules\Users\Infrastructure\Controllers\GetUserController\Mothers\{GetUserControllerMother,
    GetUserRequestMother,
    GetUserTestCaseEnum};

final class GetUserControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByOwnUserUuid(): void
    {
        $routeArgs = GetUserRequestMother::getInstance()
            ->getRequestByTestCase(GetUserTestCaseEnum::OK_AS_SUCCESS_BY_OWN_USER_UUID);

        $httpResponse = GetUserControllerMother::getControllerInstance()->show(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
    }

    public function testNokAsNotFoundByFakeUserUuid(): void
    {
        $routeArgs = GetUserRequestMother::getInstance()
            ->getRequestByTestCase(GetUserTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID);

        $httpResponse = GetUserControllerMother::getControllerInstance()->show(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $httpResponse->getStatusCode());
    }

    public function testNokAsNotFoundByOtherUserUuid(): void
    {
        $routeArgs = GetUserRequestMother::getInstance()
            ->getRequestByTestCase(GetUserTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID);

        $httpResponse = GetUserControllerMother::getControllerInstance()->show(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $httpResponse->getStatusCode());
    }

    public function testNokAsUnauthorizedByInvalidToken(): void
    {
        $routeArgs = GetUserRequestMother::getInstance()
            ->getRequestByTestCase(GetUserTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_INVALID_TOKEN);

        $httpResponse = GetUserControllerMother::getControllerInstance()->show(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_UNAUTHORIZED, $httpResponse->getStatusCode());
    }
}
