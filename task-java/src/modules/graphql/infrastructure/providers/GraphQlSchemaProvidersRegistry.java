package modules.graphql.infrastructure.providers;

import java.io.IOException;
import java.io.InputStream;
import java.io.UncheckedIOException;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.function.BiFunction;
import java.util.function.Supplier;

import modules.graphql.infrastructure.exceptions.GraphQlSchemaException;

import modules.tasks.infrastructure.providers.TasksGraphQlSchemaProvider;
import modules.users.infrastructure.providers.UsersGraphQlSchemaProvider;

/**
 * Une lo que registran los modulos en getAllModulesProviders(): la cabecera comun (schema.graphql) mas el
 * SDL de cada proveedor, y la tabla tipo.campo => resolver. Es a /graphql lo que Routes al ApiRouter.
 */
public final class GraphQlSchemaProvidersRegistry {

    // junto a esta clase en el classpath (pom.xml copia src/ sin los .java)
    private static final String SCHEMA_FILE = "schema.graphql";

    private final List<InterfaceGraphQlSchemaProvider> schemaProvidersInstances;

    private GraphQlSchemaProvidersRegistry() {
        this.schemaProvidersInstances = getAllModulesProviders().stream().map(Supplier::get).toList();
    }

    public static GraphQlSchemaProvidersRegistry getInstance() {
        return new GraphQlSchemaProvidersRegistry();
    }

    /**
     * El orden importa: primero los que definen un tipo, despues los que lo extienden.
     * Cada modulo tiene su propio provider
     */
    private List<Supplier<InterfaceGraphQlSchemaProvider>> getAllModulesProviders() {
        return List.of(
            UsersGraphQlSchemaProvider::getInstance,
            TasksGraphQlSchemaProvider::getInstance
        );
    }

    /**
     * En el orden del registro: el modulo que define un tipo va antes que los que lo extienden.
     */
    public String getGraphQlSchemaAsString() {
        List<String> allSchemas = new ArrayList<>();
        allSchemas.add(getResourceContent(GraphQlSchemaProvidersRegistry.class, SCHEMA_FILE));
        schemaProvidersInstances.forEach(
            schemaProviderInstance -> allSchemas.add(schemaProviderInstance.getGraphQlFileContent())
        );
        return String.join("\n", allSchemas);
    }

    /**
     * Une los getControllerResolvers() de cada provider en una sola tabla tipo => campo => resolver. Los tipos y
     * campos salen en el orden del registro: primero los de Users y detras los que anade Tasks. Cada resolver es
     * un BiFunction (gqlPayload, parent) que crea su controller solo si la consulta pide ese campo.
     *
     * Con el registro actual (Users, Tasks) devuelve:
     * {
     *     "Query" => {
     *         "users"     => (gqlPayload, parent) -> ListUsersGraphQlController.getInstance().invoke(),     // Users
     *         "user"      => (gqlPayload, parent) -> GetUserGraphQlController...,                          // Users
     *         "tasks"     => (gqlPayload, parent) -> SearchTasksGraphQlController...,                      // Tasks
     *         "task"      => (gqlPayload, parent) -> GetTaskGraphQlController...,                          // Tasks
     *     },
     *     "Mutation" => {
     *         "createUser" => ..., "logIn" => ..., "updateUser" => ..., "deleteUser" => ...,
     *         "regenerateApiToken" => ...,                                                             // Users
     *         "createTask" => ..., "updateTask" => ..., "deleteTask" => ...,                           // Tasks
     *     },
     *     "User" => {
     *         "tasks" => (gqlPayload, user) -> SearchTasksByUserGraphQlController...,                  // Tasks
     *     },
     * }
     * "Query.tasks" y "User.tasks" no chocan: la clave de choque es tipo + campo, no solo el campo.
     *
     * @throws GraphQlSchemaException si dos modulos resuelven el mismo campo (uno pisaria al otro sin avisar)
     */
    public Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> getAllMergedResolversByType() {
        Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> resolversByType = new LinkedHashMap<>();
        for (InterfaceGraphQlSchemaProvider schemaProviderInstance : schemaProvidersInstances) {
            schemaProviderInstance.getControllerResolvers().forEach((typeName, controllerResolvers) -> {
                Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> typeResolvers =
                    resolversByType.computeIfAbsent(typeName, key -> new LinkedHashMap<>());
                List<String> repeatedFields = controllerResolvers.keySet().stream().filter(typeResolvers::containsKey).toList();
                if (!repeatedFields.isEmpty()) {
                    throw GraphQlSchemaException.fieldsAlreadyHaveResolver(
                        typeName,
                        repeatedFields,
                        schemaProviderInstance.getClass().getName()
                    );
                }
                typeResolvers.putAll(controllerResolvers);
            });
        }
        return resolversByType;
    }

    /**
     * El file_get_contents(__DIR__."/x.graphql") de PHP: un .graphql del classpath junto a anchorClass (lo usan
     * tambien los proveedores para leer su <modulo>.graphql).
     */
    public static String getResourceContent(Class<?> anchorClass, String resourcePath) {
        try (InputStream inputStream = anchorClass.getResourceAsStream(resourcePath)) {
            if (inputStream == null) {
                throw new UncheckedIOException(new IOException(resourcePath + " not found"));
            }
            return new String(inputStream.readAllBytes(), StandardCharsets.UTF_8);
        }
        catch (IOException e) {
            throw new UncheckedIOException(e);
        }
    }
}
