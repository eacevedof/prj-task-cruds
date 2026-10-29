<?php
declare(strict_types=1);

namespace App\Modules\Graphql\Infrastructure\Exceptions;

use Exception;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;

use App\Modules\Graphql\Domain\Enums\GraphqlMessageEnum;

/**
 * El registro de /graphql esta mal montado (src/Core/config/graphql_schema.php y los XxxGraphqlSchemaProvider): es un
 * fallo de programacion, no de la peticion, asi que se responde 500 y se loguea.
 */
final class GraphqlSchemaException extends Exception
{
    /**
     * @param string[] $fieldNames
     */
    public static function fieldsAlreadyHaveResolver(string $typeName, array $fieldNames, string $schemaProviderClass): self
    {
        throw new self(
            __(GraphqlMessageEnum::GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X, $typeName, implode(", ", $fieldNames), $schemaProviderClass),
            ExceptionEnum::CODE_INTERNAL_SERVER_ERROR
        );
    }

    /**
     * @param string[] $fieldNames
     */
    public static function fieldsWithoutResolver(string $typeName, array $fieldNames): self
    {
        throw new self(
            __(GraphqlMessageEnum::GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER, $typeName, implode(", ", $fieldNames)),
            ExceptionEnum::CODE_INTERNAL_SERVER_ERROR
        );
    }
}
