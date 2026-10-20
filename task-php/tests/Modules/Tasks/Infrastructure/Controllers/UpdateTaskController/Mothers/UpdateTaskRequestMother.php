<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\UpdateTaskController\Mothers;

use App\Modules\Tasks\Domain\Enums\TaskStatusEnum;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;
use Tests\Modules\Shared\Infrastructure\Repositories\TasksRawRepositoryMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;

final class UpdateTaskRequestMother
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return array<string, string> argumentos de UpdateTaskController::update()
     */
    public function getRequestByTestCase(
        UpdateTaskTestCaseEnum $useCase
    ): array
    {
        return match ($useCase) {
            UpdateTaskTestCaseEnum::OK_AS_SUCCESS_BY_VALID_FIELDS =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->setRouteParams(["taskUuid" => TasksRawRepositoryMock::TASK_UUID_TO_UPDATE])
                    ->setPostRawParams(["task_title" => "Llamar al banco (hecho)", "task_status" => TaskStatusEnum::DONE, "target_date" => TasksRawRepositoryMock::TARGET_DATE_OK])
                    ->getMockedInstance(),

            UpdateTaskTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_TASK_TYPE =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->setRouteParams(["taskUuid" => TasksRawRepositoryMock::TASK_UUID_TO_UPDATE])
                    ->setPostRawParams(["task_title" => "Llamar al banco", "task_type" => TasksRawRepositoryMock::TASK_TYPE_INVALID])
                    ->getMockedInstance(),

            UpdateTaskTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->setRouteParams(["taskUuid" => TasksRawRepositoryMock::TASK_UUID_NOK])
                    ->setPostRawParams(["task_title" => "Llamar al banco"])
                    ->getMockedInstance(),

            UpdateTaskTestCaseEnum::NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_TO_UPDATE)
                    ->setRouteParams(["taskUuid" => TasksRawRepositoryMock::TASK_UUID_TO_UPDATE])
                    ->setPostRawParams(["task_title" => "Llamar al banco"])
                    ->getMockedInstance(),
        };
    }
}
