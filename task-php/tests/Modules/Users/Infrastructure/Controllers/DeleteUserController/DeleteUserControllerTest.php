<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\DeleteUserController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use Tests\AbstractTestCase;
use Tests\Modules\Users\Infrastructure\Controllers\DeleteUserController\Mothers\{DeleteUserControllerMother,
    DeleteUserRequestMother,
    DeleteUserTestCaseEnum};

final class DeleteUserControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByOwnUserUuid(): void
    {
        $routeArgs = DeleteUserRequestMother::getInstance()
            ->getRequestByTestCase(DeleteUserTestCaseEnum::OK_AS_SUCCESS_BY_OWN_USER_UUID);

        $httpResponse = DeleteUserControllerMother::getControllerInstance()->delete(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
    }

    public function testNokAsNotFoundByFakeUserUuid(): void
    {
        $routeArgs = DeleteUserRequestMother::getInstance()
            ->getRequestByTestCase(DeleteUserTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID);

        $httpResponse = DeleteUserControllerMother::getControllerInstance()->delete(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $httpResponse->getStatusCode());
    }

    public function testNokAsUnauthorizedByMissingToken(): void
    {
        $routeArgs = DeleteUserRequestMother::getInstance()
            ->getRequestByTestCase(DeleteUserTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN);

        $httpResponse = DeleteUserControllerMother::getControllerInstance()->delete(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_UNAUTHORIZED, $httpResponse->getStatusCode());
    }
}
