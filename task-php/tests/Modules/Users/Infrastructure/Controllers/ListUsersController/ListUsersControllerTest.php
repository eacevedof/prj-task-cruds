<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\ListUsersController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use Tests\AbstractTestCase;
use Tests\Modules\Users\Infrastructure\Controllers\ListUsersController\Mothers\{ListUsersControllerMother,
    ListUsersRequestMother,
    ListUsersTestCaseEnum};

final class ListUsersControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByValidToken(): void
    {
        $routeArgs = ListUsersRequestMother::getInstance()
            ->getRequestByTestCase(ListUsersTestCaseEnum::OK_AS_SUCCESS_BY_VALID_TOKEN);

        $httpResponse = ListUsersControllerMother::getControllerInstance()->index(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
    }

    public function testNokAsUnauthorizedByMissingToken(): void
    {
        $routeArgs = ListUsersRequestMother::getInstance()
            ->getRequestByTestCase(ListUsersTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN);

        $httpResponse = ListUsersControllerMother::getControllerInstance()->index(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_UNAUTHORIZED, $httpResponse->getStatusCode());
    }
}
