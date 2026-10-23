<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\CreateUserController\Mothers;

use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;
use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;

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
            CreateUserTestCaseEnum::OK_AS_CREATED_BY_VALID_FIELDS =>
                HttpRequestMock::getInstance()
                    ->setPostRawParams(["user_name" => "Nuevo Test", "user_email" => UsersRawRepositoryMock::USER_EMAIL_NEW])
                    ->getMockedInstance(),

            CreateUserTestCaseEnum::NOK_AS_BAD_REQUEST_BY_EMPTY_FIELDS =>
                HttpRequestMock::getInstance()
                    ->setPostRawParams(["user_name" => "   ", "user_email" => ""])
                    ->getMockedInstance(),

            CreateUserTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_EMAIL =>
                HttpRequestMock::getInstance()
                    ->setPostRawParams(["user_name" => "Nuevo Test", "user_email" => UsersRawRepositoryMock::USER_EMAIL_INVALID])
                    ->getMockedInstance(),

            CreateUserTestCaseEnum::NOK_AS_CONFLICT_BY_EXISTING_EMAIL =>
                HttpRequestMock::getInstance()
                    ->setPostRawParams(["user_name" => "Nuevo Test", "user_email" => UsersRawRepositoryMock::USER_EMAIL_OK])
                    ->getMockedInstance(),
        };
    }
}
