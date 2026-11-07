<?php
declare(strict_types=1);

namespace App\Modules\GraphQl\Infrastructure\Providers;

use App\Modules\GraphQl\Infrastructure\Exceptions\GraphQlSchemaException;

/**
 * Une lo que le registran los modulos: la cabecera comun (schema.graphql) mas el SDL de cada proveedor, y la tabla
 * tipo.campo => resolver. Es a /graphql lo que el EventBus a los eventos: no conoce a ningun modulo, es Core quien
 * le registra los providers al arrancar (src/Core/config/graphql_schema_providers.php, como event_bus.php).
 */
final class GraphQlSchemaProvidersRegistry
{
    /** @var class-string<InterfaceGraphQlSchemaProvider>[] */
    private array $schemaProvidersClasses = [];
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance) return self::$instance;
        self::$instance = new self();
        return self::$instance;
    }

    /**
     * El orden importa: primero los que definen un tipo, despues los que lo extienden. Solo guarda las clases: cada
     * provider se instancia cuando llega una peticion a /graphql.
     *
     * @param class-string<InterfaceGraphQlSchemaProvider>[] $schemaProvidersClasses
     */
    public function registerSchemaProviders(array $schemaProvidersClasses): void
    {
        $this->schemaProvidersClasses = [...$this->schemaProvidersClasses, ...$schemaProvidersClasses];
    }

    /**
     * @return InterfaceGraphQlSchemaProvider[]
     */
    private function getSchemaProvidersInstances(): array
    {
        return array_map(
            fn(string $schemaProviderClass): InterfaceGraphQlSchemaProvider => $schemaProviderClass::getInstance(),
            $this->schemaProvidersClasses
        );
    }

    /**
     * En el orden del registro: el modulo que define un tipo va antes que los que lo extienden.
     */
    public function getGraphQlSchemaAsString(): string
    {
        /**
         * @var array<string> $allSchemas
         */
        $allSchemas = array_map(
            fn (InterfaceGraphQlSchemaProvider $schemaProviderInstance): string =>
                $schemaProviderInstance->getGraphQlFileContent(),
            $this->getSchemaProvidersInstances()
        );
        return implode("\n", [
            (string) file_get_contents(__DIR__."/schema.graphql"),
            ...$allSchemas,
        ]);
    }

    /**
     * Une los getControllerResolvers() de cada provider en una sola tabla tipo => campo => resolver. Los tipos y
     * campos salen en el orden del registro: primero los de Users y detras los que anade Tasks. Cada resolver es
     * un closure fn(array $gqlPayload, mixed $parent) que crea su controller solo si la consulta pide ese campo.
     *
     * Con el registro actual (Users, Tasks) devuelve:
     * [
     *     "Query" => [
     *         "users"     => fn() => ListUsersGraphQlController::getInstance()->invoke(),          // Users
     *         "user"      => fn(array $gqlPayload) => GetUserGraphQlController...,                      // Users
     *         "tasks"     => fn(array $gqlPayload) => SearchTasksGraphQlController...,                  // Tasks
     *         "task"      => fn(array $gqlPayload) => GetTaskGraphQlController...,                      // Tasks
     *     ],
     *     "Mutation" => [
     *         "createUser" => ..., "logIn" => ..., "updateUser" => ..., "deleteUser" => ...,
     *         "regenerateApiToken" => ...,                                                         // Users
     *         "createTask" => ..., "updateTask" => ..., "deleteTask" => ...,                       // Tasks
     *     ],
     *     "User" => [
     *         "tasks" => fn(array $gqlPayload, array $user) => SearchTasksByUserGraphQlController...,   // Tasks
     *     ],
     * ]
     * "Query.tasks" y "User.tasks" no chocan: la clave de choque es tipo + campo, no solo el campo.
     *
     * @return array<string, array<string, \Closure>>
     * @throws GraphQlSchemaException si dos modulos resuelven el mismo campo (uno pisaria al otro sin avisar)
     */
    public function getAllMergedResolversByType(): array
    {
        $resolversByType = [];
        foreach ($this->getSchemaProvidersInstances() as $schemaProviderInstance) {
            foreach ($schemaProviderInstance->getControllerResolvers() as $typeName => $controllerResolvers) {
                if ($repeatedFields = array_intersect_key($controllerResolvers, $resolversByType[$typeName] ?? [])) {
                    GraphQlSchemaException::fieldsAlreadyHaveResolver(
                        $typeName,
                        array_keys($repeatedFields),
                        $schemaProviderInstance::class
                    );
                }

                $resolversByType[$typeName] = [
                    ...($resolversByType[$typeName] ?? []),
                    ...$controllerResolvers
                ];
            }
        }
        return $resolversByType;
    }
}
