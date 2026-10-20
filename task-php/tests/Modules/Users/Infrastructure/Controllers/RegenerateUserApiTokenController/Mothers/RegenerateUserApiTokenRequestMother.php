<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\RegenerateUserApiTokenController\Mothers;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;

final class RegenerateUserApiTokenRequestMother
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return array<string, string> argumentos de RegenerateUserApiTokenController::regenerate()
     */
    public function getRequestByTestCase(
        RegenerateUserApiTokenTestCaseEnum $useCase
    ): array
    {
        return match ($useCase) {
            RegenerateUserApiTokenTestCaseEnum::OK_AS_SUCCESS_BY_OWN_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_TO_REGENERATE)
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_TO_REGENERATE])
                    ->getMockedInstance(),

            RegenerateUserApiTokenTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_TO_UPDATE])
                    ->getMockedInstance(),

            RegenerateUserApiTokenTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN =>
                HttpRequestMock::getInstance()
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_OK])
                    ->getMockedInstance(),
        };
    }
}
