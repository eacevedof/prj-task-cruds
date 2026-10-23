<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\DeleteUserController\Mothers;

use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;
use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;

final class DeleteUserRequestMother
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return array<string, string> argumentos de DeleteUserController::delete()
     */
    public function getRequestByTestCase(
        DeleteUserTestCaseEnum $useCase
    ): array
    {
        return match ($useCase) {
            DeleteUserTestCaseEnum::OK_AS_SUCCESS_BY_EXISTING_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_TO_DELETE])
                    ->getMockedInstance(),

            DeleteUserTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_NOK])
                    ->getMockedInstance(),
        };
    }
}
