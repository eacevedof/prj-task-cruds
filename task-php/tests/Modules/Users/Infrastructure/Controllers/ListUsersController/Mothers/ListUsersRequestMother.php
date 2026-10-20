<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\ListUsersController\Mothers;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;

final class ListUsersRequestMother
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return array<string, string> argumentos de ListUsersController::index()
     */
    public function getRequestByTestCase(
        ListUsersTestCaseEnum $useCase
    ): array
    {
        return match ($useCase) {
            ListUsersTestCaseEnum::OK_AS_SUCCESS_BY_VALID_TOKEN =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->getMockedInstance(),

            ListUsersTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN =>
                HttpRequestMock::getInstance()
                    ->getMockedInstance(),
        };
    }
}
