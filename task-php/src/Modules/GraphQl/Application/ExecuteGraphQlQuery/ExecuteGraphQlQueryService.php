<?php
declare(strict_types=1);

namespace App\Modules\GraphQl\Application\ExecuteGraphQlQuery;

use GraphQL\Executor\Executor;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\QueryDepth;

use App\Modules\GraphQl\Domain\Enums\GraphQlEnum;
use App\Modules\GraphQl\Infrastructure\Exceptions\GraphQlSchemaException;
use App\Modules\GraphQl\Infrastructure\Providers\GraphQlSchemaProvidersRegistry;


/**
 * Monta el schema con lo que registran los modulos y ejecuta la peticion con webonyx: el unico sitio (junto a
 * GraphQlException) que conoce la libreria. Servicio: no captura ni loguea.
 */
final readonly class ExecuteGraphQlQueryService
{
    private function __construct(
        private GraphQlSchemaProvidersRegistry $graphqlSchemaRegistry,
    ) {
    }

    public static function getInstance(): self
    {
        return new self(GraphQlSchemaProvidersRegistry::getInstance());
    }

    /**
     * Los errores de GraphQL (sintaxis, validacion, dominio de un resolver) van en el resultado, no se lanzan.
     *
     * @throws GraphQlSchemaException si el registro esta mal montado (campo repetido o sin resolver)
     */
    public function invoke(
        ExecuteGraphQlRequestDto $executeGraphQlRequestDto
    ): ExecuteGraphQlResultDto
    {
        $executionResult = GraphQL::executeQuery(
            $this->getSchema(),
            $executeGraphQlRequestDto->getQuery(),
            variableValues: $executeGraphQlRequestDto->getVariables(),
            operationName: $executeGraphQlRequestDto->getOperationName(),
            validationRules: array_merge(
                DocumentValidator::allRules(),
                [new QueryDepth(GraphQlEnum::MAX_QUERY_DEPTH)]
            ),
        );
        return ExecuteGraphQlResultDto::fromPrimitives($executionResult->toArray());
    }

    /**
     * @throws GraphQlSchemaException
     */
    private function getSchema(): Schema
    {
        $resolversByType = $this->graphqlSchemaRegistry->getAllMergedResolversByType();
        $schema = BuildSchema::build(
            //todos los esquemas .graphql de los modulos, en el orden del registro, con la cabecera comun schema.graphql
            $this->graphqlSchemaRegistry->getGraphQlSchemaAsString(),

            function (array $typeConfig) use ($resolversByType): array {
                if (!$resolvers = $resolversByType[$typeConfig["name"]] ?? [])
                    return $typeConfig;

                // los campos sin resolver propio salen del array del ResultDto (resolver por defecto)
                $typeConfig["resolveField"] = fn(
                    mixed $objectValue,
                    array $gqlPayload,
                    mixed $context,
                    ResolveInfo $resolveInfo
                ) =>
                    isset($resolvers[$resolveInfo->fieldName])
                        ? $resolvers[$resolveInfo->fieldName]($gqlPayload, $objectValue)
                        : Executor::defaultFieldResolver($objectValue, $gqlPayload, $context, $resolveInfo);

                return $typeConfig;
            }

        );
        $this->failIfRootFieldWithoutResolver($schema, $resolversByType);
        return $schema;
    }

    /**
     * Un campo de Query o Mutation sin resolver devolveria null sin avisar: es un fallo del registro.
     * @throws GraphQlSchemaException
     */
    private function failIfRootFieldWithoutResolver(Schema $schema, array $resolversByType): void
    {
        foreach ([GraphQlEnum::TYPE_QUERY, GraphQlEnum::TYPE_MUTATION] as $rootTypeName) {
            /** @var ObjectType $rootType */
            $rootType = $schema->getType($rootTypeName);
            if ($fieldsWithoutResolver = array_diff(
                $rootType->getFieldNames(),
                array_keys($resolversByType[$rootTypeName] ?? [])
            )) {
                GraphQlSchemaException::fieldsWithoutResolver(
                    $rootTypeName,
                    $fieldsWithoutResolver
                );
            }
        }

    }

}
