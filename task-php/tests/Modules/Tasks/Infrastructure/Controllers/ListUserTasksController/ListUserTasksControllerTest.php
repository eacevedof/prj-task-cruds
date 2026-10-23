<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\ListUserTasksController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use Tests\AbstractTestCase;
use Tests\Modules\Tasks\Infrastructure\Controllers\ListUserTasksController\Mothers\{
    ListUserTasksTestCaseEnum,
    ListUserTasksRequestMother,
    ListUserTasksControllerMother
};

final class ListUserTasksControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByExistingUserUuid(): void
    {
        $routeArgs = ListUserTasksRequestMother::getInstance()
            ->getRequestByTestCase(ListUserTasksTestCaseEnum::OK_AS_SUCCESS_BY_EXISTING_USER_UUID);

        $httpResponse = ListUserTasksControllerMother::getControllerInstance()->index(...$routeArgs);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
    }

    public function testNokAsNotFoundByFakeUserUuid(): void
    {
        $routeArgs = ListUserTasksRequestMother::getInstance()
            ->getRequestByTestCase(ListUserTasksTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID);

        $httpResponse = ListUserTasksControllerMother::getControllerInstance()->index(...$routeArgs);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $httpResponse->getStatusCode());
    }

}
