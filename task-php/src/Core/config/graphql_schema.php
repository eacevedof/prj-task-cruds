<?php
declare(strict_types=1);

use App\Modules\Users\Infrastructure\Providers\UsersGraphqlSchemaProvider;
use App\Modules\Tasks\Infrastructure\Providers\TasksGraphqlSchemaProvider;

// Registro de los modulos en /graphql (lo lee GraphqlSchemaRegistry solo cuando llega una peticion a /graphql).
// Cada modulo nuevo anade aqui su XxxGraphqlSchemaProvider, despues de los modulos cuyos tipos extiende.
return [
    UsersGraphqlSchemaProvider::class,
    TasksGraphqlSchemaProvider::class,
];
