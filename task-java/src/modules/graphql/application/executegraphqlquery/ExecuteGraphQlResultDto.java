package modules.graphql.application.executegraphqlquery;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import modules.shared.application.Primitives;

import modules.graphql.domain.enums.GraphQlEnum;

/**
 * Respuesta GraphQL {errors?, data?}: las claves solo van si traen algo y en el orden de webonyx (errors primero).
 */
public final class ExecuteGraphQlResultDto {

    private final List<Map<String, Object>> errors;
    private final Object data;

    public ExecuteGraphQlResultDto(List<Map<String, Object>> errors, Object data) {
        this.errors = errors;
        this.data = data;
    }

    /**
     * graphqlResult: el ExecutionResult::toArray() de webonyx que monta ExecuteGraphQlQueryService.
     */
    public static ExecuteGraphQlResultDto fromPrimitives(Map<String, Object> graphqlResult) {
        return new ExecuteGraphQlResultDto(
            Primitives.getPrimitivesList(graphqlResult.get(GraphQlEnum.KEY_ERRORS)),
            graphqlResult.get(GraphQlEnum.KEY_DATA)
        );
    }

    /**
     * Error antes de ejecutar (peticion sin query, fallo inesperado): sin data, como lo daria GraphQL.
     */
    public static ExecuteGraphQlResultDto fromErrorMessage(String message) {
        return new ExecuteGraphQlResultDto(List.of(Primitives.of(GraphQlEnum.KEY_MESSAGE, message)), null);
    }

    public Map<String, Object> toArray() {
        Map<String, Object> graphqlResult = new LinkedHashMap<>();
        if (!errors.isEmpty()) graphqlResult.put(GraphQlEnum.KEY_ERRORS, errors);
        if (data != null) graphqlResult.put(GraphQlEnum.KEY_DATA, data);
        return graphqlResult;
    }
}
