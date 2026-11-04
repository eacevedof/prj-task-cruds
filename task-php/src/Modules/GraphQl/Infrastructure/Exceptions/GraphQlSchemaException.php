<?php
declare(strict_types=1);

namespace App\Modules\GraphQl\Infrastructure\Exceptions;

use Exception;
use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\GraphQl\Domain\Enums\GraphQlMessageEnum;

/**
 * El registro de /graphql esta mal montado: es un fallo del programador, no de la peticion. Salta al montar el
 * schema, que en PHP se monta en cada peticion, asi que rompe TODAS las llamadas a /graphql (aunque la query no
 * toque el campo afectado). La captura el catch Throwable de ExecuteGraphQlQueryController: el mensaje va al log y
 * el cliente solo recibe un 500 con el texto generico, sin data.
 */
final class GraphQlSchemaException extends Exception
{
    /**
     * Dos providers registran resolver para el mismo tipo.campo: el segundo pisaria al primero sin avisar.
     * La lanza GraphQlSchemaProvidersRegistry::getAllMergedResolversByType() al unir los providers en el orden de
     * getAllModulesProviders().
     *
     * Ejemplo: TasksGraphQlSchemaProvider anade por error "user" en Query, que ya es de Users
     *   $typeName            "Query"
     *   $fieldNames          ["user"]  (o varios: ["tasks", "task"] -> "Query.tasks, task")
     *   $schemaProviderClass "App\Modules\Tasks\Infrastructure\Providers\TasksGraphQlSchemaProvider"
     *                        (el que llega SEGUNDO y choca, no el que ya tenia el campo)
     *   mensaje: graphql: Query.user already has a resolver (App\Modules\Tasks\...\TasksGraphQlSchemaProvider)
     *
     * @param string $typeName tipo del schema: "Query", "Mutation" o uno de negocio como "User"
     * @param string[] $fieldNames campos repetidos de ese tipo
     * @param string $schemaProviderClass FQCN del provider que intenta registrarlos de nuevo
     */
    public static function fieldsAlreadyHaveResolver(
        string $typeName,
        array $fieldNames,
        string $schemaProviderClass
    ): never
    {
        throw new self(
            __(
                GraphQlMessageEnum::GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X,
                $typeName,
                implode(", ", $fieldNames),
                $schemaProviderClass
            ),
            ExceptionEnum::CODE_INTERNAL_SERVER_ERROR
        );
    }

    /**
     * Un campo de Query o Mutation esta en el .graphql pero ningun provider le da resolver: sin esto devolveria null
     * sin avisar. La lanza ExecuteGraphQlQueryService::failIfRootFieldWithoutResolver() tras montar el schema.
     * Solo se comprueban los tipos raiz: un campo de un tipo de negocio sin resolver (p. ej. User.tasks) no salta
     * aqui, lo resuelve el resolver por defecto leyendo del array padre (y daria null).
     *
     * Ejemplo: se anade "archiveTask(task_uuid: ID!): Task" al "extend type Mutation" de tasks.graphql y se olvida
     * el resolver en TasksGraphQlSchemaProvider::getMutationControllers()
     *   $typeName   "Mutation"
     *   $fieldNames [8 => "archiveTask"]  (sale de array_diff: conserva las claves, implode las ignora)
     *   mensaje: graphql: Mutation.archiveTask has no resolver
     *
     * @param string $typeName solo "Query" o "Mutation" (GraphQlEnum::TYPE_QUERY / TYPE_MUTATION)
     * @param string[] $fieldNames campos declarados en el schema sin resolver registrado
     */
    public static function fieldsWithoutResolver(
        string $typeName,
        array $fieldNames
    ): never
    {
        throw new self(
            __(GraphQlMessageEnum::GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER,
                $typeName,
                implode(", ", $fieldNames)
            ),
            ExceptionEnum::CODE_INTERNAL_SERVER_ERROR
        );
    }
    
}
