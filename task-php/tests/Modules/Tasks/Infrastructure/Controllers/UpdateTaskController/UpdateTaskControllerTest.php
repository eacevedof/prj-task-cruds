<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\UpdateTaskController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use Tests\AbstractTestCase;
use Tests\Modules\Tasks\Infrastructure\Controllers\UpdateTaskController\Mothers\{
    UpdateTaskTestCaseEnum,
    UpdateTaskRequestMother,
    UpdateTaskControllerMother
};

final class UpdateTaskControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByValidFields(): void
    {
        $routeArgs = UpdateTaskRequestMother::getInstance()
            ->getRequestByTestCase(UpdateTaskTestCaseEnum::OK_AS_SUCCESS_BY_VALID_FIELDS);

        $httpResponse = UpdateTaskControllerMother::getControllerInstance()->update(...$routeArgs);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
    }

    public function testNokAsBadRequestByInvalidTaskType(): void
    {
        $routeArgs = UpdateTaskRequestMother::getInstance()
            ->getRequestByTestCase(UpdateTaskTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_TASK_TYPE);

        $httpResponse = UpdateTaskControllerMother::getControllerInstance()->update(...$routeArgs);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $httpResponse->getStatusCode());
    }

    public function testNokAsNotFoundByFakeTaskUuid(): void
    {
        $routeArgs = UpdateTaskRequestMother::getInstance()
            ->getRequestByTestCase(UpdateTaskTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID);

        $httpResponse = UpdateTaskControllerMother::getControllerInstance()->update(...$routeArgs);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $httpResponse->getStatusCode());
    }

}
