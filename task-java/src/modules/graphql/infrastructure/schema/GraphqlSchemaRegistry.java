package modules.graphql.infrastructure.schema;

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

import core.config.GraphqlSchema;

import modules.graphql.infrastructure.exceptions.GraphqlSchemaException;

/**
 * Une lo que registran los modulos en src/core/config/GraphqlSchema.java: la cabecera comun (schema.graphql) mas el
 * SDL de cada proveedor, y la tabla tipo.campo => resolver. Es a /graphql lo que Routes al ApiRouter.
 */
public final class GraphqlSchemaRegistry {

    // junto a esta clase en el classpath (pom.xml copia src/ sin los .java)
    private static final String SCHEMA_FILE = "schema.graphql";

    private final List<InterfaceGraphqlSchemaProvider> schemaProviders;

    private GraphqlSchemaRegistry(List<Supplier<InterfaceGraphqlSchemaProvider>> schemaProviderFactories) {
        this.schemaProviders = schemaProviderFactories.stream().map(Supplier::get).toList();
    }

    public static GraphqlSchemaRegistry getInstance() {
        return new GraphqlSchemaRegistry(GraphqlSchema.getSchemaProviders());
    }

    /**
     * En el orden del registro: el modulo que define un tipo va antes que los que lo extienden.
     */
    public String getSdl() {
        List<String> sdlParts = new ArrayList<>();
        sdlParts.add(getResourceContent(GraphqlSchemaRegistry.class, SCHEMA_FILE));
        schemaProviders.forEach(schemaProvider -> sdlParts.add(schemaProvider.getSdl()));
        return String.join("\n", sdlParts);
    }

    /**
     * @throws GraphqlSchemaException si dos modulos resuelven el mismo campo (uno pisaria al otro sin avisar)
     */
    public Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> getResolversByType() {
        Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> resolversByType = new LinkedHashMap<>();
        for (InterfaceGraphqlSchemaProvider schemaProvider : schemaProviders) {
            schemaProvider.getResolversByType().forEach((typeName, resolvers) -> {
                Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> typeResolvers =
                    resolversByType.computeIfAbsent(typeName, key -> new LinkedHashMap<>());
                List<String> repeatedFields = resolvers.keySet().stream().filter(typeResolvers::containsKey).toList();
                if (!repeatedFields.isEmpty()) {
                    throw GraphqlSchemaException.fieldsAlreadyHaveResolver(
                        typeName,
                        repeatedFields,
                        schemaProvider.getClass().getName()
                    );
                }
                typeResolvers.putAll(resolvers);
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
