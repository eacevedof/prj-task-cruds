<?php
declare(strict_types=1);

namespace App\Modules\Graphql\Infrastructure\Components;

use GraphQL\Executor\Executor;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\QueryDepth;

use App\Modules\Graphql\Domain\Enums\GraphqlEnum;
use App\Modules\Graphql\Infrastructure\Dtos\GraphqlRequestDto;
use App\Modules\Graphql\Infrastructure\Dtos\GraphqlResultDto;
use App\Modules\Graphql\Infrastructure\Exceptions\GraphqlSchemaException;
use App\Modules\Graphql\Infrastructure\Schema\GraphqlSchemaRegistry;

/**
 * Monta el schema con lo que registran los modulos y ejecuta la peticion con webonyx: el unico sitio (junto a
 * AbstractGraphqlController y su Error) que conoce la libreria. Componente: no captura ni loguea.
 */
final readonly class GraphqlExecutor
{
    private function __construct(
        private GraphqlSchemaRegistry $graphqlSchemaRegistry,
    ) {
    }

    public static function getInstance(): self
    {
        return new self(GraphqlSchemaRegistry::getInstance());
    }

    /**
     * Los errores de GraphQL (sintaxis, validacion, dominio de un resolver) van en el resultado, no se lanzan.
     *
     * @throws GraphqlSchemaException si el registro esta mal montado (campo repetido o sin resolver)
     */
    public function execute(GraphqlRequestDto $graphqlRequestDto): GraphqlResultDto
    {
        $executionResult = GraphQL::executeQuery(
            $this->getSchema(),
            $graphqlRequestDto->getQuery(),
            variableValues: $graphqlRequestDto->getVariables(),
            operationName: $graphqlRequestDto->getOperationName(),
            validationRules: array_merge(
                DocumentValidator::allRules(),
                [new QueryDepth(GraphqlEnum::MAX_QUERY_DEPTH)]
            ),
        );
        return GraphqlResultDto::fromPrimitives($executionResult->toArray());
    }

    /**
     * @throws GraphqlSchemaException
     */
    private function getSchema(): Schema
    {
        $resolversByType = $this->graphqlSchemaRegistry->getResolversByType();
        $schema = BuildSchema::build(
            $this->graphqlSchemaRegistry->getSdl(),
            function (array $typeConfig) use ($resolversByType): array {
                if (!$resolvers = $resolversByType[$typeConfig["name"]] ?? []) return $typeConfig;

                // los campos sin resolver propio salen del array del ResultDto (resolver por defecto)
                $typeConfig["resolveField"] = fn(mixed $objectValue, array $args, mixed $context, ResolveInfo $resolveInfo) =>
                    isset($resolvers[$resolveInfo->fieldName])
                        ? $resolvers[$resolveInfo->fieldName]($args, $objectValue)
                        : Executor::defaultFieldResolver($objectValue, $args, $context, $resolveInfo);
                return $typeConfig;
            }
        );
        $this->failIfRootFieldWithoutResolver($schema, $resolversByType);
        return $schema;
    }

    /**
     * Un campo de Query o Mutation sin resolver devolveria null sin avisar: es un fallo del registro.
     *
     * @throws GraphqlSchemaException
     */
    private function failIfRootFieldWithoutResolver(Schema $schema, array $resolversByType): void
    {
        foreach ([GraphqlEnum::TYPE_QUERY, GraphqlEnum::TYPE_MUTATION] as $rootTypeName) {
            /** @var ObjectType $rootType */
            $rootType = $schema->getType($rootTypeName);
            if ($fieldsWithoutResolver = array_diff($rootType->getFieldNames(), array_keys($resolversByType[$rootTypeName] ?? []))) {
                GraphqlSchemaException::fieldsWithoutResolver($rootTypeName, $fieldsWithoutResolver);
            }
        }
    }
}
