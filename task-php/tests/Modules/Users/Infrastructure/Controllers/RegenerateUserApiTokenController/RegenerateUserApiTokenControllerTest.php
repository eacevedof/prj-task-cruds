<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\RegenerateUserApiTokenController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use Tests\AbstractTestCase;
use Tests\Modules\Users\Infrastructure\Controllers\RegenerateUserApiTokenController\Mothers\{
    RegenerateUserApiTokenControllerMother, RegenerateUserApiTokenRequestMother, RegenerateUserApiTokenTestCaseEnum};

final class RegenerateUserApiTokenControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByOwnUserUuid(): void
    {
        $routeArgs = RegenerateUserApiTokenRequestMother::getInstance()
            ->getRequestByTestCase(RegenerateUserApiTokenTestCaseEnum::OK_AS_SUCCESS_BY_OWN_USER_UUID);

        $httpResponse = RegenerateUserApiTokenControllerMother::getControllerInstance()->regenerate(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
    }

    public function testNokAsNotFoundByOtherUserUuid(): void
    {
        $routeArgs = RegenerateUserApiTokenRequestMother::getInstance()
            ->getRequestByTestCase(RegenerateUserApiTokenTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID);

        $httpResponse = RegenerateUserApiTokenControllerMother::getControllerInstance()->regenerate(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $httpResponse->getStatusCode());
    }

    public function testNokAsUnauthorizedByMissingToken(): void
    {
        $routeArgs = RegenerateUserApiTokenRequestMother::getInstance()
            ->getRequestByTestCase(RegenerateUserApiTokenTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN);

        $httpResponse = RegenerateUserApiTokenControllerMother::getControllerInstance()->regenerate(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_UNAUTHORIZED, $httpResponse->getStatusCode());
    }
}
