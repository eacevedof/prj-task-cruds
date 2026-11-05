package modules.graphql.infrastructure.providers;

import java.util.Map;
import java.util.function.BiFunction;

/**
 * Lo que un modulo aporta a /graphql: su parte del schema y quien resuelve cada campo. Cada modulo tiene el suyo en
 * infrastructure/providers y se registra en GraphQlSchemaProvidersRegistry.getAllModulesProviders().
 * El getInstance() estatico de PHP no cabe en una interfaz de Java: cada provider lo declara y el registro lo usa como
 * Supplier.
 */
public interface InterfaceGraphQlSchemaProvider {

    /**
     * SDL del modulo (su <modulo>.graphql): define tipos o extiende los de otro modulo con "extend type".
     */
    String getGraphQlFileContent();

    /**
     * Tipo => campo => fn(gqlPayload, parent): devuelve el mapa del ResultDto o lanza una GraphQlException.
     * En el orden en que el modulo los declara (LinkedHashMap).
     */
    Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> getControllerResolvers();
}
