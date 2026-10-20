<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\ListUserTasksController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use Tests\AbstractTestCase;
use Tests\Modules\Tasks\Infrastructure\Controllers\ListUserTasksController\Mothers\{ListUserTasksControllerMother,
    ListUserTasksRequestMother,
    ListUserTasksTestCaseEnum};

final class ListUserTasksControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByOwnUserUuid(): void
    {
        $routeArgs = ListUserTasksRequestMother::getInstance()
            ->getRequestByTestCase(ListUserTasksTestCaseEnum::OK_AS_SUCCESS_BY_OWN_USER_UUID);

        $httpResponse = ListUserTasksControllerMother::getControllerInstance()->index(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
    }

    public function testNokAsNotFoundByFakeUserUuid(): void
    {
        $routeArgs = ListUserTasksRequestMother::getInstance()
            ->getRequestByTestCase(ListUserTasksTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID);

        $httpResponse = ListUserTasksControllerMother::getControllerInstance()->index(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $httpResponse->getStatusCode());
    }

    public function testNokAsNotFoundByOtherUserUuid(): void
    {
        $routeArgs = ListUserTasksRequestMother::getInstance()
            ->getRequestByTestCase(ListUserTasksTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID);

        $httpResponse = ListUserTasksControllerMother::getControllerInstance()->index(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $httpResponse->getStatusCode());
    }
}
