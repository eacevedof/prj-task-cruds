<?php
declare(strict_types=1);

return [
    ["url" => "/health", "controller" => "App\Modules\Shared\Infrastructure\Controllers\HealthController", "method" => "invoke", "allowed" => ["get"]],

    //@documentation (publica; fuente: los .md de src/Modules/Open/Documentation/Infrastructure/Views)
    ["url" => "/api/v1", "controller" => "App\Modules\Open\Documentation\Infrastructure\Controllers\DocumentationController", "method" => "invoke", "allowed" => ["get"]],
    ["url" => "/api/v1/changelog", "controller" => "App\Modules\Open\Documentation\Infrastructure\Controllers\ChangelogController", "method" => "invoke", "allowed" => ["get"]],

    //@auth
    ["url" => "/api/v1/log-in", "controller" => "App\Modules\Users\Infrastructure\Controllers\LogInUserRestController", "method" => "invoke", "allowed" => ["post"]],

    /**
     * @uses App\Modules\GraphQl\Infrastructure\Controllers\ExecuteGraphQlQueryController
     * @graphql (cada modulo registra su XxxGraphQlSchemaProvider en src/Core/config/graphql_schema_providers.php)
     */
    [
        "url" => "/graphql",
        "controller" => "App\Modules\GraphQl\Infrastructure\Controllers\ExecuteGraphQlQueryController",
        "method" => "invoke",
        "allowed" => ["post"]
    ],
];
