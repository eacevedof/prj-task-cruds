<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\DeleteTaskController\Mothers;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;
use Tests\Modules\Shared\Infrastructure\Repositories\TasksRawRepositoryMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;

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
            DeleteTaskTestCaseEnum::OK_AS_SUCCESS_BY_OWN_TASK_UUID =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->setRouteParams(["taskUuid" => TasksRawRepositoryMock::TASK_UUID_TO_DELETE])
                    ->getMockedInstance(),

            DeleteTaskTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->setRouteParams(["taskUuid" => TasksRawRepositoryMock::TASK_UUID_NOK])
                    ->getMockedInstance(),

            DeleteTaskTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN =>
                HttpRequestMock::getInstance()
                    ->setRouteParams(["taskUuid" => TasksRawRepositoryMock::TASK_UUID_OK])
                    ->getMockedInstance(),
        };
    }
}
