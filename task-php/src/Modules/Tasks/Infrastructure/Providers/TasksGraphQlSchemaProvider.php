<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Providers;

use App\Modules\GraphQl\Domain\Enums\GraphQlEnum;
use App\Modules\GraphQl\Infrastructure\Providers\InterfaceGraphQlSchemaProvider;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Infrastructure\Providers\UsersGraphQlSchemaProvider;

use App\Modules\Tasks\Infrastructure\Controllers\CreateTaskGraphQlController;
use App\Modules\Tasks\Infrastructure\Controllers\DeleteTaskGraphQlController;
use App\Modules\Tasks\Infrastructure\Controllers\GetTaskGraphQlController;
use App\Modules\Tasks\Infrastructure\Controllers\SearchTasksByUserGraphQlController;
use App\Modules\Tasks\Infrastructure\Controllers\SearchTasksGraphQlController;
use App\Modules\Tasks\Infrastructure\Controllers\UpdateTaskGraphQlController;

/**
 * Parte de tasks en /graphql: tasks.graphql (extiende Query, Mutation y User de users) y sus resolvers, incluido
 * User.tasks, que cuelga de User pero es de tasks. Va despues de UsersGraphQlSchemaProvider en el registro.
 */
final class TasksGraphQlSchemaProvider implements InterfaceGraphQlSchemaProvider
{
    public static function getInstance(): self
    {
        return new self();
    }

    public function getGraphQlFileContent(): string
    {
        return (string) file_get_contents(__DIR__."/tasks.graphql");
    }

    /**
     * @throws \Throwable
     */
    public function getControllerResolvers(): array
    {
        return [
            GraphQlEnum::TYPE_QUERY => $this->getQueryControllers(),

            GraphQlEnum::TYPE_MUTATION => $this->getMutationControllers(),

            // Las tareas de un usuario.
            // User.tasks: solo ejemplo de extender un tipo de otro modulo, no lo consume ningun front (ver tasks.graphql)
            UsersGraphQlSchemaProvider::TYPE_USER => [
                "tasks" => fn(array $gqlPayload, array $user) => SearchTasksByUserGraphQlController::getInstance()->invoke(
                    (string)($user[UsersKeyEnum::USER_UUID] ?? ""),
                    $gqlPayload
                ),
            ],
        ];

    }

    private function getQueryControllers(): array
    {
        return [
            "tasks" => fn(array $gqlPayload) => SearchTasksGraphQlController::getInstance()->invoke($gqlPayload),
            "task" => fn(array $gqlPayload) => GetTaskGraphQlController::getInstance()->invoke($gqlPayload),
        ];
    }

    private function getMutationControllers(): array
    {
        return [
            "createTask" => fn(array $gqlPayload) => CreateTaskGraphQlController::getInstance()->invoke($gqlPayload),
            "updateTask" => fn(array $gqlPayload) => UpdateTaskGraphQlController::getInstance()->invoke($gqlPayload),
            "deleteTask" => fn(array $gqlPayload) => DeleteTaskGraphQlController::getInstance()->invoke($gqlPayload),
        ];
    }

}
