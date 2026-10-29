package modules.graphql.infrastructure.dtos;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import modules.shared.application.Primitives;

import modules.graphql.domain.enums.GraphqlEnum;

/**
 * Respuesta GraphQL {errors?, data?}: las claves solo van si traen algo y en el orden de webonyx (errors primero).
 */
public final class GraphqlResultDto {

    private final List<Map<String, Object>> errors;
    private final Object data;

    public GraphqlResultDto(List<Map<String, Object>> errors, Object data) {
        this.errors = errors;
        this.data = data;
    }

    /**
     * graphqlResult: el ExecutionResult::toArray() de webonyx que monta GraphqlExecutor.
     */
    public static GraphqlResultDto fromPrimitives(Map<String, Object> graphqlResult) {
        return new GraphqlResultDto(
            Primitives.getPrimitivesList(graphqlResult.get(GraphqlEnum.KEY_ERRORS)),
            graphqlResult.get(GraphqlEnum.KEY_DATA)
        );
    }

    /**
     * Error antes de ejecutar (peticion sin query, fallo inesperado): sin data, como lo daria GraphQL.
     */
    public static GraphqlResultDto fromErrorMessage(String message) {
        return new GraphqlResultDto(List.of(Primitives.of(GraphqlEnum.KEY_MESSAGE, message)), null);
    }

    public Map<String, Object> toArray() {
        Map<String, Object> graphqlResult = new LinkedHashMap<>();
        if (!errors.isEmpty()) graphqlResult.put(GraphqlEnum.KEY_ERRORS, errors);
        if (data != null) graphqlResult.put(GraphqlEnum.KEY_DATA, data);
        return graphqlResult;
    }
}
