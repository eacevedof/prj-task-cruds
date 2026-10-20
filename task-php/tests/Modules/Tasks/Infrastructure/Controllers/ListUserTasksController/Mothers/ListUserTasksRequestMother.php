<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\ListUserTasksController\Mothers;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;

final class ListUserTasksRequestMother
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return array<string, string> argumentos de ListUserTasksController::index()
     */
    public function getRequestByTestCase(
        ListUserTasksTestCaseEnum $useCase
    ): array
    {
        return match ($useCase) {
            ListUserTasksTestCaseEnum::OK_AS_SUCCESS_BY_OWN_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_OK])
                    ->getMockedInstance(),

            ListUserTasksTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_NOK])
                    ->getMockedInstance(),

            ListUserTasksTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_TO_UPDATE)
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_OK])
                    ->getMockedInstance(),
        };
    }
}
