<?php
declare(strict_types=1);

/**
 * Rutas de la API (ApiRouter). "url" con tags ":nombre" -> argumento con ese nombre en el metodo del
 * controller. "allowed": metodos HTTP en minusculas (HEAD va implicito en GET).
 * Solo login (REST) y GraphQL: el resto de operaciones van por /graphql.
 * Auth: /graphql exige "Authorization: Bearer <api_token>" salvo createUser y logIn. No lo decide el router: cada
 * resolver autentica con getAuthUserOrFail() (AbstractGraphqlController).
 */
return [
    ["url" => "/health", "controller" => "App\Modules\Shared\Infrastructure\Controllers\HealthController", "method" => "index", "allowed" => ["get"]],

    //@documentation (publica; fuente: los .md de src/Modules/Open/Documentation/Infrastructure/Views)
    ["url" => "/api/v1", "controller" => "App\Modules\Open\Documentation\Infrastructure\Controllers\DocumentationController", "method" => "show", "allowed" => ["get"]],
    ["url" => "/api/v1/changelog", "controller" => "App\Modules\Open\Documentation\Infrastructure\Controllers\ChangelogController", "method" => "show", "allowed" => ["get"]],

    //@auth
    ["url" => "/api/v1/log-in", "controller" => "App\Modules\Users\Infrastructure\Controllers\LogInUserRestController", "method" => "logIn", "allowed" => ["post"]],

    //@graphql (cada modulo se registra en src/Core/config/graphql_schema.php con su XxxGraphqlSchemaProvider)
    ["url" => "/graphql", "controller" => "App\Modules\Graphql\Infrastructure\Controllers\GraphqlController", "method" => "execute", "allowed" => ["post"]],
];
