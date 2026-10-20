<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\ListTasksController\Mothers;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;

final class ListTasksRequestMother
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return array<string, string> argumentos de ListTasksController::index()
     */
    public function getRequestByTestCase(
        ListTasksTestCaseEnum $useCase
    ): array
    {
        return match ($useCase) {
            ListTasksTestCaseEnum::OK_AS_SUCCESS_BY_VALID_TOKEN =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->getMockedInstance(),

            ListTasksTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN =>
                HttpRequestMock::getInstance()
                    ->getMockedInstance(),
        };
    }
}
