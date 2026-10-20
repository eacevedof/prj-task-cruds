<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\CreateUserController\Mothers;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;

final class CreateUserRequestMother
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return array<string, string> argumentos de CreateUserController::create()
     */
    public function getRequestByTestCase(
        CreateUserTestCaseEnum $useCase
    ): array
    {
        return match ($useCase) {
            CreateUserTestCaseEnum::OK_AS_CREATED_BY_VALID_FIELDS_WITHOUT_TOKEN =>
                HttpRequestMock::getInstance()
                    ->setPostRawParams([
                        "user_name" => UsersRawRepositoryMock::USER_NAME_NEW,
                        "user_email" => UsersRawRepositoryMock::USER_EMAIL_NEW,
                        "password" => UsersRawRepositoryMock::USER_PASSWORD_NEW,
                    ])
                    ->getMockedInstance(),

            CreateUserTestCaseEnum::NOK_AS_BAD_REQUEST_BY_EMPTY_FIELDS =>
                HttpRequestMock::getInstance()
                    ->setPostRawParams(["user_name" => "   ", "user_email" => "", "password" => ""])
                    ->getMockedInstance(),

            CreateUserTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_EMAIL =>
                HttpRequestMock::getInstance()
                    ->setPostRawParams([
                        "user_name" => UsersRawRepositoryMock::USER_NAME_OTHER,
                        "user_email" => UsersRawRepositoryMock::USER_EMAIL_INVALID,
                        "password" => UsersRawRepositoryMock::USER_PASSWORD_NEW,
                    ])
                    ->getMockedInstance(),

            CreateUserTestCaseEnum::NOK_AS_CONFLICT_BY_EXISTING_EMAIL =>
                HttpRequestMock::getInstance()
                    ->setPostRawParams([
                        "user_name" => UsersRawRepositoryMock::USER_NAME_OTHER,
                        "user_email" => UsersRawRepositoryMock::USER_EMAIL_OK,
                        "password" => UsersRawRepositoryMock::USER_PASSWORD_NEW,
                    ])
                    ->getMockedInstance(),

            // mismo user_name que uno existente cambiando mayusculas: el login no las distingue
            CreateUserTestCaseEnum::NOK_AS_CONFLICT_BY_EXISTING_USER_NAME =>
                HttpRequestMock::getInstance()
                    ->setPostRawParams([
                        "user_name" => strtoupper(UsersRawRepositoryMock::USER_NAME_OK),
                        "user_email" => UsersRawRepositoryMock::USER_EMAIL_NEW_GRAPHQL,
                        "password" => UsersRawRepositoryMock::USER_PASSWORD_NEW,
                    ])
                    ->getMockedInstance(),

            CreateUserTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_USER_NAME_AND_SHORT_PASSWORD =>
                HttpRequestMock::getInstance()
                    ->setPostRawParams([
                        "user_name" => UsersRawRepositoryMock::USER_NAME_INVALID,
                        "user_email" => UsersRawRepositoryMock::USER_EMAIL_NEW,
                        "password" => "short",
                    ])
                    ->getMockedInstance(),
        };
    }
}
