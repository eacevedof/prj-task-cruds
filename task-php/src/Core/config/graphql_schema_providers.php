<?php
declare(strict_types=1);

use App\Modules\GraphQl\Infrastructure\Providers\GraphQlSchemaProvidersRegistry;
use App\Modules\Tasks\Infrastructure\Providers\TasksGraphQlSchemaProvider;
use App\Modules\Users\Infrastructure\Providers\UsersGraphQlSchemaProvider;

// Registro de los modulos en /graphql, como event_bus.php con los subscribers: Core conoce a todos y se los entrega al
// registro, que no conoce a ninguno (los modulos dependen de GraphQl, nunca al reves).
// El orden importa: cada modulo nuevo anade aqui su XxxGraphQlSchemaProvider, despues de los modulos cuyos tipos extiende.
GraphQlSchemaProvidersRegistry::getInstance()->registerSchemaProviders([
    UsersGraphQlSchemaProvider::class,
    TasksGraphQlSchemaProvider::class,
]);
