<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\LogInUserRestController\Mothers;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;

final class LogInUserRequestMother
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return array<string, string> argumentos de LogInUserRestController::logIn() (ninguno: todo va en el body)
     */
    public function getRequestByTestCase(
        LogInUserTestCaseEnum $useCase
    ): array
    {
        return match ($useCase) {
            // user_name no distingue mayusculas
            LogInUserTestCaseEnum::OK_AS_SUCCESS_BY_VALID_CREDENTIALS_WITH_OTHER_CASE =>
                HttpRequestMock::getInstance()
                    ->setPostRawParams([
                        "user_name" => strtoupper(UsersRawRepositoryMock::USER_NAME_TO_LOG_IN),
                        "password" => UsersRawRepositoryMock::USER_PASSWORD_TO_LOG_IN,
                    ])
                    ->getMockedInstance(),

            LogInUserTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD =>
                HttpRequestMock::getInstance()
                    ->setPostRawParams([
                        "user_name" => UsersRawRepositoryMock::USER_NAME_TO_LOG_IN,
                        "password" => UsersRawRepositoryMock::USER_PASSWORD_NOK,
                    ])
                    ->getMockedInstance(),

            LogInUserTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_UNKNOWN_USER_NAME =>
                HttpRequestMock::getInstance()
                    ->setPostRawParams([
                        "user_name" => UsersRawRepositoryMock::USER_NAME_NOK,
                        "password" => UsersRawRepositoryMock::USER_PASSWORD_TO_LOG_IN,
                    ])
                    ->getMockedInstance(),

            // cuenta sin password (como las creadas antes de add-password-hash-to-users): no puede entrar
            LogInUserTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_USER_WITHOUT_PASSWORD =>
                HttpRequestMock::getInstance()
                    ->setPostRawParams([
                        "user_name" => UsersRawRepositoryMock::USER_NAME_OK,
                        "password" => UsersRawRepositoryMock::USER_PASSWORD_TO_LOG_IN,
                    ])
                    ->getMockedInstance(),
        };
    }
}
