<?php
declare(strict_types=1);

/**
 * Rutas de la API (ApiRouter). "url" con tags ":nombre" -> argumento con ese nombre en el metodo del
 * controller. "allowed": metodos HTTP en minusculas (HEAD va implicito en GET).
 */
return [
    ["url" => "/health", "controller" => "App\Modules\Shared\Infrastructure\Controllers\HealthController", "method" => "index", "allowed" => ["get"]],

    //@users
    ["url" => "/api/v1/users", "controller" => "App\Modules\Users\Infrastructure\Controllers\ListUsersController", "method" => "index", "allowed" => ["get"]],
    ["url" => "/api/v1/users", "controller" => "App\Modules\Users\Infrastructure\Controllers\CreateUserController", "method" => "create", "allowed" => ["post"]],
    ["url" => "/api/v1/users/:userUuid", "controller" => "App\Modules\Users\Infrastructure\Controllers\GetUserController", "method" => "show", "allowed" => ["get"]],
    ["url" => "/api/v1/users/:userUuid", "controller" => "App\Modules\Users\Infrastructure\Controllers\UpdateUserController", "method" => "update", "allowed" => ["put"]],
    ["url" => "/api/v1/users/:userUuid", "controller" => "App\Modules\Users\Infrastructure\Controllers\DeleteUserController", "method" => "delete", "allowed" => ["delete"]],

    //@tasks (1 user -> n tasks)
    ["url" => "/api/v1/users/:userUuid/tasks", "controller" => "App\Modules\Tasks\Infrastructure\Controllers\ListUserTasksController", "method" => "index", "allowed" => ["get"]],
    ["url" => "/api/v1/users/:userUuid/tasks", "controller" => "App\Modules\Tasks\Infrastructure\Controllers\CreateTaskController", "method" => "create", "allowed" => ["post"]],
    ["url" => "/api/v1/tasks", "controller" => "App\Modules\Tasks\Infrastructure\Controllers\ListTasksController", "method" => "index", "allowed" => ["get"]],
    ["url" => "/api/v1/tasks/:taskUuid", "controller" => "App\Modules\Tasks\Infrastructure\Controllers\GetTaskController", "method" => "show", "allowed" => ["get"]],
    ["url" => "/api/v1/tasks/:taskUuid", "controller" => "App\Modules\Tasks\Infrastructure\Controllers\UpdateTaskController", "method" => "update", "allowed" => ["put"]],
    ["url" => "/api/v1/tasks/:taskUuid", "controller" => "App\Modules\Tasks\Infrastructure\Controllers\DeleteTaskController", "method" => "delete", "allowed" => ["delete"]],
];
