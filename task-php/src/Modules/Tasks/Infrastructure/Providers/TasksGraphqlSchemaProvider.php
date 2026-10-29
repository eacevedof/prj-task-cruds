<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Providers;

use App\Modules\Graphql\Domain\Enums\GraphqlEnum;
use App\Modules\Graphql\Infrastructure\Schema\InterfaceGraphqlSchemaProvider;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;

use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;
use App\Modules\Tasks\Infrastructure\Controllers\CreateTaskGraphqlController;
use App\Modules\Tasks\Infrastructure\Controllers\DeleteTaskGraphqlController;
use App\Modules\Tasks\Infrastructure\Controllers\GetTaskGraphqlController;
use App\Modules\Tasks\Infrastructure\Controllers\SearchTasksByUserGraphqlController;
use App\Modules\Tasks\Infrastructure\Controllers\SearchTasksGraphqlController;
use App\Modules\Tasks\Infrastructure\Controllers\UpdateTaskGraphqlController;

/**
 * Parte de tasks en /graphql: tasks.graphql (extiende Query, Mutation y User de users) y sus resolvers, incluido
 * User.tasks, que cuelga de User pero es de tasks. Va despues de UsersGraphqlSchemaProvider en el registro.
 */
final class TasksGraphqlSchemaProvider implements InterfaceGraphqlSchemaProvider
{
    public static function getInstance(): self
    {
        return new self();
    }

    public function getSdl(): string
    {
        return (string) file_get_contents(__DIR__."/tasks.graphql");
    }

    public function getResolversByType(): array
    {
        return [
            GraphqlEnum::TYPE_QUERY => [
                "tasks" => fn(array $args) => SearchTasksGraphqlController::getInstance()->invoke($args),
                "task" => fn(array $args) => GetTaskGraphqlController::getInstance()->invoke($args),
                "userTasks" => fn(array $args) => SearchTasksByUserGraphqlController::getInstance()->invoke(
                    (string) ($args[TasksKeyEnum::USER_UUID] ?? ""),
                    $args
                ),
            ],
            GraphqlEnum::TYPE_MUTATION => [
                "createTask" => fn(array $args) => CreateTaskGraphqlController::getInstance()->invoke($args),
                "updateTask" => fn(array $args) => UpdateTaskGraphqlController::getInstance()->invoke($args),
                "deleteTask" => fn(array $args) => DeleteTaskGraphqlController::getInstance()->invoke($args),
            ],
            GraphqlEnum::TYPE_USER => [
                "tasks" => fn(array $args, array $user) => SearchTasksByUserGraphqlController::getInstance()->invoke(
                    (string) ($user[UsersKeyEnum::USER_UUID] ?? ""),
                    $args
                ),
            ],
        ];
    }
}
