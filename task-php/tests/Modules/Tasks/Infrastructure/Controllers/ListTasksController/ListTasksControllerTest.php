<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\ListTasksController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use Tests\AbstractTestCase;
use Tests\Modules\Tasks\Infrastructure\Controllers\ListTasksController\Mothers\{ListTasksControllerMother,
    ListTasksRequestMother,
    ListTasksTestCaseEnum};

final class ListTasksControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByValidToken(): void
    {
        $routeArgs = ListTasksRequestMother::getInstance()
            ->getRequestByTestCase(ListTasksTestCaseEnum::OK_AS_SUCCESS_BY_VALID_TOKEN);

        $httpResponse = ListTasksControllerMother::getControllerInstance()->index(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
    }

    public function testNokAsUnauthorizedByMissingToken(): void
    {
        $routeArgs = ListTasksRequestMother::getInstance()
            ->getRequestByTestCase(ListTasksTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN);

        $httpResponse = ListTasksControllerMother::getControllerInstance()->index(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_UNAUTHORIZED, $httpResponse->getStatusCode());
    }
}
