<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\DeleteTaskController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use Tests\AbstractTestCase;
use Tests\Modules\Tasks\Infrastructure\Controllers\DeleteTaskController\Mothers\{
    DeleteTaskTestCaseEnum,
    DeleteTaskRequestMother,
    DeleteTaskControllerMother
};

final class DeleteTaskControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByExistingTaskUuid(): void
    {
        $routeArgs = DeleteTaskRequestMother::getInstance()
            ->getRequestByTestCase(DeleteTaskTestCaseEnum::OK_AS_SUCCESS_BY_EXISTING_TASK_UUID);

        $httpResponse = DeleteTaskControllerMother::getControllerInstance()->delete(...$routeArgs);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
    }

    public function testNokAsNotFoundByFakeTaskUuid(): void
    {
        $routeArgs = DeleteTaskRequestMother::getInstance()
            ->getRequestByTestCase(DeleteTaskTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID);

        $httpResponse = DeleteTaskControllerMother::getControllerInstance()->delete(...$routeArgs);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $httpResponse->getStatusCode());
    }

}
