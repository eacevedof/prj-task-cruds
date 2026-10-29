<?php
declare(strict_types=1);

namespace App\Modules\Graphql\Infrastructure\Schema;

/**
 * Lo que un modulo aporta a /graphql: su parte del schema y quien resuelve cada campo. Cada modulo tiene el suyo en
 * Infrastructure/Providers y se registra en src/Core/config/graphql_schema.php.
 */
interface InterfaceGraphqlSchemaProvider
{
    public static function getInstance(): self;

    /**
     * SDL del modulo (su <modulo>.graphql): define tipos o extiende los de otro modulo con "extend type".
     */
    public function getSdl(): string;

    /**
     * Tipo => campo => fn(array $args, mixed $parent): devuelve el array del ResultDto o lanza un GraphQL\Error.
     */
    public function getResolversByType(): array;
}
