<?php
declare(strict_types=1);

namespace App\Modules\Shared\Domain\Enums;

/**
 * Claves del protocolo GraphQL sobre HTTP (body de la peticion y errores de la respuesta).
 */
abstract class GraphqlEnum
{
    public const string KEY_QUERY = "query";
    public const string KEY_VARIABLES = "variables";
    public const string KEY_OPERATION_NAME = "operationName";
    public const string KEY_ERRORS = "errors";
    public const string KEY_MESSAGE = "message";

    // tipos de schema.graphql que tienen resolvers propios
    public const string TYPE_QUERY = "Query";
    public const string TYPE_MUTATION = "Mutation";
    public const string TYPE_USER = "User";

    public const string EXTENSION_CODE = "code";
    public const string EXTENSION_ERRORS = "errors";

    // User -> tasks -> campo es profundidad 3: 5 deja margen sin abrir la puerta a consultas abusivas
    public const int MAX_QUERY_DEPTH = 5;
}
