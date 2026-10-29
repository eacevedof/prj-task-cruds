package modules.graphql.infrastructure.schema;

import java.util.Map;
import java.util.function.BiFunction;

/**
 * Lo que un modulo aporta a /graphql: su parte del schema y quien resuelve cada campo. Cada modulo tiene el suyo en
 * infrastructure/providers y se registra en src/core/config/GraphqlSchema.java.
 */
public interface InterfaceGraphqlSchemaProvider {

    /**
     * SDL del modulo (su <modulo>.graphql): define tipos o extiende los de otro modulo con "extend type".
     */
    String getSdl();

    /**
     * Tipo => campo => fn(args, parent): devuelve el mapa del ResultDto o lanza un GraphqlErrorException.
     * En el orden en que el modulo los declara (LinkedHashMap).
     */
    Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> getResolversByType();
}
