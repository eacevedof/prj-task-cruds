<?php
declare(strict_types=1);

/**
 * Rutas de la API (ApiRouter). "url" con tags ":nombre" -> argumento con ese nombre en el metodo del
 * controller. "allowed": metodos HTTP en minusculas (HEAD va implicito en GET).
 * Auth: todo /api/v1/* y /graphql exigen "Authorization: Bearer <api_token>" salvo el alta (POST /api/v1/users),
 * el login (POST /api/v1/log-in) y la documentacion. No lo decide el router: cada controller autentica con getAuthUserOrFail() (AbstractApiController / AbstractGraphqlController).
 */
return [
    ["url" => "/health", "controller" => "App\Modules\Shared\Infrastructure\Controllers\HealthController", "method" => "index", "allowed" => ["get"]],

    //@documentation (publica; fuente: los .md de src/Modules/Open/Documentation/Infrastructure/Views)
    ["url" => "/api/v1/documentation", "controller" => "App\Modules\Open\Documentation\Infrastructure\Controllers\DocumentationController", "method" => "show", "allowed" => ["get"]],
    ["url" => "/api/v1/documentation/changelog", "controller" => "App\Modules\Open\Documentation\Infrastructure\Controllers\ChangelogController", "method" => "show", "allowed" => ["get"]],

    //@users
    ["url" => "/api/v1/log-in", "controller" => "App\Modules\Users\Infrastructure\Controllers\LogInUserController", "method" => "logIn", "allowed" => ["post"]],
    ["url" => "/api/v1/users", "controller" => "App\Modules\Users\Infrastructure\Controllers\ListUsersController", "method" => "index", "allowed" => ["get"]],
    ["url" => "/api/v1/users", "controller" => "App\Modules\Users\Infrastructure\Controllers\CreateUserController", "method" => "create", "allowed" => ["post"]],
    ["url" => "/api/v1/users/:userUuid", "controller" => "App\Modules\Users\Infrastructure\Controllers\GetUserController", "method" => "show", "allowed" => ["get"]],
    ["url" => "/api/v1/users/:userUuid", "controller" => "App\Modules\Users\Infrastructure\Controllers\UpdateUserController", "method" => "update", "allowed" => ["put"]],
    ["url" => "/api/v1/users/:userUuid", "controller" => "App\Modules\Users\Infrastructure\Controllers\DeleteUserController", "method" => "delete", "allowed" => ["delete"]],
    ["url" => "/api/v1/users/:userUuid/api-token", "controller" => "App\Modules\Users\Infrastructure\Controllers\RegenerateUserApiTokenController", "method" => "regenerate", "allowed" => ["post"]],

    //@tasks (1 user -> n tasks)
    ["url" => "/api/v1/users/:userUuid/tasks", "controller" => "App\Modules\Tasks\Infrastructure\Controllers\ListUserTasksController", "method" => "index", "allowed" => ["get"]],
    ["url" => "/api/v1/tasks", "controller" => "App\Modules\Tasks\Infrastructure\Controllers\ListTasksController", "method" => "index", "allowed" => ["get"]],
    ["url" => "/api/v1/tasks", "controller" => "App\Modules\Tasks\Infrastructure\Controllers\CreateTaskController", "method" => "create", "allowed" => ["post"]],
    ["url" => "/api/v1/tasks/:taskUuid", "controller" => "App\Modules\Tasks\Infrastructure\Controllers\GetTaskController", "method" => "show", "allowed" => ["get"]],
    ["url" => "/api/v1/tasks/:taskUuid", "controller" => "App\Modules\Tasks\Infrastructure\Controllers\UpdateTaskController", "method" => "update", "allowed" => ["put"]],
    ["url" => "/api/v1/tasks/:taskUuid", "controller" => "App\Modules\Tasks\Infrastructure\Controllers\DeleteTaskController", "method" => "delete", "allowed" => ["delete"]],

    //@graphql (mismos casos de uso que la REST; schema en src/Core/Graphql/schema.graphql)
    ["url" => "/graphql", "controller" => "App\Core\Graphql\GraphqlController", "method" => "execute", "allowed" => ["post"]],
];
