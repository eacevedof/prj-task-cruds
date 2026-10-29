<?php
declare(strict_types=1);

namespace App\Modules\Graphql\Infrastructure\Schema;

use App\Core\Boot\BootPathsEnum;

use App\Modules\Graphql\Infrastructure\Exceptions\GraphqlSchemaException;

/**
 * Une lo que registran los modulos en src/Core/config/graphql_schema.php: la cabecera comun (schema.graphql) mas el
 * SDL de cada proveedor, y la tabla tipo.campo => resolver. Es a /graphql lo que routes.php al ApiRouter.
 */
final class GraphqlSchemaRegistry
{
    /** @var InterfaceGraphqlSchemaProvider[] */
    private array $schemaProviders;

    private function __construct()
    {
        $this->schemaProviders = array_map(
            fn(string $providerClass): InterfaceGraphqlSchemaProvider => $providerClass::getInstance(),
            include BootPathsEnum::PATH_SRC_CONFIG."/graphql_schema.php"
        );
    }

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * En el orden del registro: el modulo que define un tipo va antes que los que lo extienden.
     */
    public function getSdl(): string
    {
        return implode("\n", [
            (string) file_get_contents(__DIR__."/schema.graphql"),
            ...array_map(
                fn(InterfaceGraphqlSchemaProvider $schemaProvider): string => $schemaProvider->getSdl(),
                $this->schemaProviders
            ),
        ]);
    }

    /**
     * @throws GraphqlSchemaException si dos modulos resuelven el mismo campo (uno pisaria al otro sin avisar)
     */
    public function getResolversByType(): array
    {
        $resolversByType = [];
        foreach ($this->schemaProviders as $schemaProvider) {
            foreach ($schemaProvider->getResolversByType() as $typeName => $resolvers) {
                if ($repeatedFields = array_intersect_key($resolvers, $resolversByType[$typeName] ?? [])) {
                    GraphqlSchemaException::fieldsAlreadyHaveResolver(
                        $typeName,
                        array_keys($repeatedFields),
                        $schemaProvider::class
                    );
                }
                $resolversByType[$typeName] = [...($resolversByType[$typeName] ?? []), ...$resolvers];
            }
        }
        return $resolversByType;
    }
}
