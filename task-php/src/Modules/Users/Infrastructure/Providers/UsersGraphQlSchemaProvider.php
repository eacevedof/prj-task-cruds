<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Providers;

use App\Modules\GraphQl\Domain\Enums\GraphQlEnum;
use App\Modules\GraphQl\Infrastructure\Providers\InterfaceGraphQlSchemaProvider;

use App\Modules\Users\Infrastructure\Controllers\CreateUserGraphQlController;
use App\Modules\Users\Infrastructure\Controllers\DeleteUserGraphQlController;
use App\Modules\Users\Infrastructure\Controllers\GetUserGraphQlController;
use App\Modules\Users\Infrastructure\Controllers\ListUsersGraphQlController;
use App\Modules\Users\Infrastructure\Controllers\LogInUserGraphQlController;
use App\Modules\Users\Infrastructure\Controllers\RegenerateUserApiTokenGraphQlController;
use App\Modules\Users\Infrastructure\Controllers\UpdateUserGraphQlController;

/**
 * Parte de users en /graphql: users.graphql (define Query, Mutation y User) y sus resolvers. El resolver se crea
 * solo si la consulta pide su campo.
 */
final class UsersGraphQlSchemaProvider implements InterfaceGraphQlSchemaProvider
{
    // tipo que define users.graphql y extienden otros modulos (Tasks le anade tasks)
    public const string TYPE_USER = "User";

    public static function getInstance(): self
    {
        return new self();
    }

    public function getGraphQlFileContent(): string
    {
        return (string) file_get_contents(__DIR__."/users.graphql");
    }

    public function getControllerResolvers(): array
    {
        return [
            GraphQlEnum::TYPE_QUERY => $this->getQueryControllers(),
            GraphQlEnum::TYPE_MUTATION => $this->getMutationControllers(),
        ];
    }

    private function getQueryControllers(): array
    {
        return [
            "users" => fn() => ListUsersGraphQlController::getInstance()->invoke(),
            "user" => fn(array $gqlPayload) => GetUserGraphQlController::getInstance()->invoke($gqlPayload),
        ];
    }

    private function getMutationControllers(): array
    {
        return [
            "createUser" => fn(array $gqlPayload) => CreateUserGraphQlController::getInstance()->invoke($gqlPayload),
            "logIn" => fn(array $gqlPayload) => LogInUserGraphQlController::getInstance()->invoke($gqlPayload),
            "updateUser" => fn(array $gqlPayload) => UpdateUserGraphQlController::getInstance()->invoke($gqlPayload),
            "deleteUser" => fn(array $gqlPayload) => DeleteUserGraphQlController::getInstance()->invoke($gqlPayload),
            "regenerateApiToken" => fn(array $gqlPayload) => RegenerateUserApiTokenGraphQlController::getInstance()->invoke($gqlPayload),
        ];
    }

}
