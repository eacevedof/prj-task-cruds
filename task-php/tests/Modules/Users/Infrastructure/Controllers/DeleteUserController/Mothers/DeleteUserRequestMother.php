<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\DeleteUserController\Mothers;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;

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
            DeleteUserTestCaseEnum::OK_AS_SUCCESS_BY_OWN_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_TO_DELETE)
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_TO_DELETE])
                    ->getMockedInstance(),

            DeleteUserTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_NOK])
                    ->getMockedInstance(),

            DeleteUserTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN =>
                HttpRequestMock::getInstance()
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_OK])
                    ->getMockedInstance(),
        };
    }
}
