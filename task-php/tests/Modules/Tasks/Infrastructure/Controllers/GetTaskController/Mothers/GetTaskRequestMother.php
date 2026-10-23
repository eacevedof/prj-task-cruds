<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\GetTaskController\Mothers;

use Tests\Modules\Shared\Infrastructure\Repositories\TasksRawRepositoryMock;
use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;

final class GetTaskRequestMother
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return array<string, string> argumentos de GetTaskController::show()
     */
    public function getRequestByTestCase(
        GetTaskTestCaseEnum $useCase
    ): array
    {
        return match ($useCase) {
            GetTaskTestCaseEnum::OK_AS_SUCCESS_BY_EXISTING_TASK_UUID =>
                HttpRequestMock::getInstance()
                    ->setRouteParams(["taskUuid" => TasksRawRepositoryMock::TASK_UUID_OK])
                    ->getMockedInstance(),

            GetTaskTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID =>
                HttpRequestMock::getInstance()
                    ->setRouteParams(["taskUuid" => TasksRawRepositoryMock::TASK_UUID_NOK])
                    ->getMockedInstance(),
        };
    }
}
