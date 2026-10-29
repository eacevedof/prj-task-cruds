<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Providers;

use App\Modules\Graphql\Domain\Enums\GraphqlEnum;
use App\Modules\Graphql\Infrastructure\Schema\InterfaceGraphqlSchemaProvider;

use App\Modules\Users\Infrastructure\Controllers\CreateUserGraphqlController;
use App\Modules\Users\Infrastructure\Controllers\DeleteUserGraphqlController;
use App\Modules\Users\Infrastructure\Controllers\GetUserGraphqlController;
use App\Modules\Users\Infrastructure\Controllers\ListUsersGraphqlController;
use App\Modules\Users\Infrastructure\Controllers\LogInUserGraphqlController;
use App\Modules\Users\Infrastructure\Controllers\RegenerateUserApiTokenGraphqlController;
use App\Modules\Users\Infrastructure\Controllers\UpdateUserGraphqlController;

/**
 * Parte de users en /graphql: users.graphql (define Query, Mutation y User) y sus resolvers. El resolver se crea
 * solo si la consulta pide su campo.
 */
final class UsersGraphqlSchemaProvider implements InterfaceGraphqlSchemaProvider
{
    public static function getInstance(): self
    {
        return new self();
    }

    public function getSdl(): string
    {
        return (string) file_get_contents(__DIR__."/users.graphql");
    }

    public function getResolversByType(): array
    {
        return [
            GraphqlEnum::TYPE_QUERY => [
                "users" => fn() => ListUsersGraphqlController::getInstance()->invoke(),
                "user" => fn(array $args) => GetUserGraphqlController::getInstance()->invoke($args),
            ],
            GraphqlEnum::TYPE_MUTATION => [
                "createUser" => fn(array $args) => CreateUserGraphqlController::getInstance()->invoke($args),
                "logIn" => fn(array $args) => LogInUserGraphqlController::getInstance()->invoke($args),
                "updateUser" => fn(array $args) => UpdateUserGraphqlController::getInstance()->invoke($args),
                "deleteUser" => fn(array $args) => DeleteUserGraphqlController::getInstance()->invoke($args),
                "regenerateApiToken" => fn(array $args) => RegenerateUserApiTokenGraphqlController::getInstance()->invoke($args),
            ],
        ];
    }
}
