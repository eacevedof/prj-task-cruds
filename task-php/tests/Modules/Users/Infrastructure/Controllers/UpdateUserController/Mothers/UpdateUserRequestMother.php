<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\UpdateUserController\Mothers;

use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;
use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;

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
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_TO_UPDATE])
                    ->setPostRawParams(["user_name" => "Luis Updated", "user_email" => UsersRawRepositoryMock::USER_EMAIL_UPDATED])
                    ->getMockedInstance(),

            UpdateUserTestCaseEnum::NOK_AS_BAD_REQUEST_BY_EMPTY_USER_NAME =>
                HttpRequestMock::getInstance()
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_TO_UPDATE])
                    ->setPostRawParams(["user_name" => "", "user_email" => UsersRawRepositoryMock::USER_EMAIL_UPDATED])
                    ->getMockedInstance(),

            UpdateUserTestCaseEnum::NOK_AS_CONFLICT_BY_EMAIL_OF_OTHER_USER =>
                HttpRequestMock::getInstance()
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_TO_UPDATE])
                    ->setPostRawParams(["user_name" => "Luis Updated", "user_email" => UsersRawRepositoryMock::USER_EMAIL_OK])
                    ->getMockedInstance(),

            UpdateUserTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID =>
                HttpRequestMock::getInstance()
                    ->setRouteParams(["userUuid" => UsersRawRepositoryMock::USER_UUID_NOK])
                    ->setPostRawParams(["user_name" => "Luis Updated", "user_email" => UsersRawRepositoryMock::USER_EMAIL_UPDATED])
                    ->getMockedInstance(),
        };
    }
}
