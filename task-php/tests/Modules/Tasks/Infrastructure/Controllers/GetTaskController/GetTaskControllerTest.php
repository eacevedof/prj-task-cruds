<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\GetTaskController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use Tests\AbstractTestCase;
use Tests\Modules\Tasks\Infrastructure\Controllers\GetTaskController\Mothers\{GetTaskControllerMother,
    GetTaskRequestMother,
    GetTaskTestCaseEnum};

final class GetTaskControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByOwnTaskUuid(): void
    {
        $routeArgs = GetTaskRequestMother::getInstance()
            ->getRequestByTestCase(GetTaskTestCaseEnum::OK_AS_SUCCESS_BY_OWN_TASK_UUID);

        $httpResponse = GetTaskControllerMother::getControllerInstance()->show(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
    }

    public function testNokAsNotFoundByFakeTaskUuid(): void
    {
        $routeArgs = GetTaskRequestMother::getInstance()
            ->getRequestByTestCase(GetTaskTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID);

        $httpResponse = GetTaskControllerMother::getControllerInstance()->show(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $httpResponse->getStatusCode());
    }

    public function testNokAsNotFoundByTaskOfOtherUser(): void
    {
        $routeArgs = GetTaskRequestMother::getInstance()
            ->getRequestByTestCase(GetTaskTestCaseEnum::NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER);

        $httpResponse = GetTaskControllerMother::getControllerInstance()->show(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $httpResponse->getStatusCode());
    }
}
