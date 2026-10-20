<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\GetUserController\Mothers;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;

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
            GetUserTestCaseEnum::OK_AS_SUCCESS_BY_OWN_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_OK])
                    ->getMockedInstance(),

            GetUserTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_NOK])
                    ->getMockedInstance(),

            GetUserTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_TO_UPDATE)
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_OK])
                    ->getMockedInstance(),

            GetUserTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_INVALID_TOKEN =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_NOK)
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_OK])
                    ->getMockedInstance(),
        };
    }
}
