<?php
declare(strict_types=1);

namespace App\Modules\GraphQl\Infrastructure\Providers;

/**
 * Lo que un modulo aporta a /graphql: su parte del schema y quien resuelve cada campo. Cada modulo tiene el suyo en
 * Infrastructure/Providers y se registra en GraphQlSchemaProvidersRegistry::getAllModulesProviders().
 */
interface InterfaceGraphQlSchemaProvider
{
    public static function getInstance(): self;

    /**
     * SDL del modulo (su <modulo>.graphql): define tipos o extiende los de otro modulo con "extend type".
     */
    public function getGraphQlFileContent(): string;

    /**
     * Tipo => campo => fn(array $gqlPayload, mixed $parent): devuelve el array del ResultDto o lanza una GraphQlException.
     */
    public function getControllerResolvers(): array;
}
