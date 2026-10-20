<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\UpdateUserController\Mothers;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;

final class UpdateUserRequestMother
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return array<string, string> argumentos de UpdateUserController::update()
     */
    public function getRequestByTestCase(
        UpdateUserTestCaseEnum $useCase
    ): array
    {
        return match ($useCase) {
            UpdateUserTestCaseEnum::OK_AS_SUCCESS_BY_VALID_FIELDS =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_TO_UPDATE)
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_TO_UPDATE])
                    ->setPostRawParams(["user_name" => UsersRawRepositoryMock::USER_NAME_UPDATED, "user_email" => UsersRawRepositoryMock::USER_EMAIL_UPDATED])
                    ->getMockedInstance(),

            UpdateUserTestCaseEnum::NOK_AS_BAD_REQUEST_BY_EMPTY_USER_NAME =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_TO_UPDATE)
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_TO_UPDATE])
                    ->setPostRawParams(["user_name" => "", "user_email" => UsersRawRepositoryMock::USER_EMAIL_UPDATED])
                    ->getMockedInstance(),

            UpdateUserTestCaseEnum::NOK_AS_CONFLICT_BY_EMAIL_OF_OTHER_USER =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_TO_UPDATE)
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_TO_UPDATE])
                    ->setPostRawParams(["user_name" => UsersRawRepositoryMock::USER_NAME_UPDATED, "user_email" => UsersRawRepositoryMock::USER_EMAIL_OK])
                    ->getMockedInstance(),

            UpdateUserTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_TO_UPDATE)
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_OK])
                    ->setPostRawParams(["user_name" => UsersRawRepositoryMock::USER_NAME_UPDATED, "user_email" => UsersRawRepositoryMock::USER_EMAIL_UPDATED])
                    ->getMockedInstance(),
        };
    }
}
