<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\ListUsersController\Mothers;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;

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
            ListUsersTestCaseEnum::OK_AS_SUCCESS_BY_DEFAULT =>
                HttpRequestMock::getInstance()
                    ->getMockedInstance(),
        };
    }
}
