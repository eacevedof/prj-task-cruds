<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\GetUserController\Mothers;

use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;
use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;

final class GetUserRequestMother
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return array<string, string> argumentos de GetUserController::show()
     */
    public function getRequestByTestCase(
        GetUserTestCaseEnum $useCase
    ): array
    {
        return match ($useCase) {
            GetUserTestCaseEnum::OK_AS_SUCCESS_BY_EXISTING_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_OK])
                    ->getMockedInstance(),

            GetUserTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_NOK])
                    ->getMockedInstance(),
        };
    }
}
