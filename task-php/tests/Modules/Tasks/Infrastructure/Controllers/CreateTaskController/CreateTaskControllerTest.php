<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\CreateTaskController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use Tests\AbstractTestCase;
use Tests\Modules\Tasks\Infrastructure\Controllers\CreateTaskController\Mothers\{CreateTaskControllerMother,
    CreateTaskRequestMother,
    CreateTaskTestCaseEnum};

final class CreateTaskControllerTest extends AbstractTestCase
{
    public function testOkAsCreatedByValidFields(): void
    {
        $routeArgs = CreateTaskRequestMother::getInstance()
            ->getRequestByTestCase(CreateTaskTestCaseEnum::OK_AS_CREATED_BY_VALID_FIELDS);

        $httpResponse = CreateTaskControllerMother::getControllerInstance()->create(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_CREATED, $httpResponse->getStatusCode());
    }

    public function testNokAsBadRequestByEmptyTaskTitle(): void
    {
        $routeArgs = CreateTaskRequestMother::getInstance()
            ->getRequestByTestCase(CreateTaskTestCaseEnum::NOK_AS_BAD_REQUEST_BY_EMPTY_TASK_TITLE);

        $httpResponse = CreateTaskControllerMother::getControllerInstance()->create(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $httpResponse->getStatusCode());
    }

    public function testNokAsBadRequestByInvalidTaskStatus(): void
    {
        $routeArgs = CreateTaskRequestMother::getInstance()
            ->getRequestByTestCase(CreateTaskTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_TASK_STATUS);

        $httpResponse = CreateTaskControllerMother::getControllerInstance()->create(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $httpResponse->getStatusCode());
    }

    public function testNokAsBadRequestByInvalidTargetDate(): void
    {
        $routeArgs = CreateTaskRequestMother::getInstance()
            ->getRequestByTestCase(CreateTaskTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_TARGET_DATE);

        $httpResponse = CreateTaskControllerMother::getControllerInstance()->create(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $httpResponse->getStatusCode());
    }

    public function testNokAsUnauthorizedByMissingToken(): void
    {
        $routeArgs = CreateTaskRequestMother::getInstance()
            ->getRequestByTestCase(CreateTaskTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN);

        $httpResponse = CreateTaskControllerMother::getControllerInstance()->create(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_UNAUTHORIZED, $httpResponse->getStatusCode());
    }
}
