<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\DeleteTaskController\Mothers;

use Tests\Modules\Shared\Infrastructure\Repositories\TasksRawRepositoryMock;
use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;

final class DeleteTaskRequestMother
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return array<string, string> argumentos de DeleteTaskController::delete()
     */
    public function getRequestByTestCase(
        DeleteTaskTestCaseEnum $useCase
    ): array
    {
        return match ($useCase) {
            DeleteTaskTestCaseEnum::OK_AS_SUCCESS_BY_EXISTING_TASK_UUID =>
                HttpRequestMock::getInstance()
                    ->setRouteParams(["taskUuid" => TasksRawRepositoryMock::TASK_UUID_TO_DELETE])
                    ->getMockedInstance(),

            DeleteTaskTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID =>
                HttpRequestMock::getInstance()
                    ->setRouteParams(["taskUuid" => TasksRawRepositoryMock::TASK_UUID_NOK])
                    ->getMockedInstance(),
        };
    }
}
