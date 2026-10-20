<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\CreateTaskController\Mothers;

use App\Modules\Tasks\Domain\Enums\TaskTypeEnum;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;
use Tests\Modules\Shared\Infrastructure\Repositories\TasksRawRepositoryMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;

final class CreateTaskRequestMother
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return array<string, string> argumentos de CreateTaskController::create()
     */
    public function getRequestByTestCase(
        CreateTaskTestCaseEnum $useCase
    ): array
    {
        return match ($useCase) {
            CreateTaskTestCaseEnum::OK_AS_CREATED_BY_VALID_FIELDS =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->setPostRawParams(["task_title" => "Nueva task", "task_type" => TaskTypeEnum::WORK, "target_date" => TasksRawRepositoryMock::TARGET_DATE_OK])
                    ->getMockedInstance(),

            CreateTaskTestCaseEnum::NOK_AS_BAD_REQUEST_BY_EMPTY_TASK_TITLE =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->setPostRawParams(["task_title" => "  "])
                    ->getMockedInstance(),

            CreateTaskTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_TASK_STATUS =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->setPostRawParams(["task_title" => "Nueva task", "task_status" => TasksRawRepositoryMock::TASK_STATUS_INVALID])
                    ->getMockedInstance(),

            CreateTaskTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_TARGET_DATE =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->setPostRawParams(["task_title" => "Nueva task", "target_date" => TasksRawRepositoryMock::TARGET_DATE_INVALID])
                    ->getMockedInstance(),

            CreateTaskTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN =>
                HttpRequestMock::getInstance()
                    ->setPostRawParams(["task_title" => "Nueva task"])
                    ->getMockedInstance(),
        };
    }
}
