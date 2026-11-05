package modules.graphql.application.executegraphqlquery;

import java.util.LinkedHashMap;
import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.components.json.JsonSerializer;

import modules.graphql.domain.enums.GraphQlEnum;

/**
 * Body de POST /graphql: {query, variables?, operationName?}.
 */
public final class ExecuteGraphQlRequestDto {

    private final String query;
    private final Map<String, Object> variables;
    private final String operationName;

    public ExecuteGraphQlRequestDto(String query, Map<String, Object> variables, String operationName) {
        this.query = query;
        this.variables = variables;
        this.operationName = operationName;
    }

    public static ExecuteGraphQlRequestDto fromPrimitives(Map<String, Object> primitives) {
        String operationName = getScalarAsString(primitives.get(GraphQlEnum.KEY_OPERATION_NAME));
        return new ExecuteGraphQlRequestDto(
            getScalarAsString(primitives.get(GraphQlEnum.KEY_QUERY)),
            getVariablesAsMap(primitives.get(GraphQlEnum.KEY_VARIABLES)),
            operationName.isEmpty() ? null : operationName
        );
    }

    /**
     * Un array/objeto donde va un string cuenta como vacio (como HttpRequester::getPostString), no como su toString().
     */
    private static String getScalarAsString(Object value) {
        return Primitives.getTrimmedString(value);
    }

    /**
     * "variables" puede llegar como objeto JSON o, en algunos clientes, como string JSON.
     */
    private static Map<String, Object> getVariablesAsMap(Object variables) {
        if (variables instanceof String variablesJson) {
            variables = JsonSerializer.getInstance().getDecoded(variablesJson);
        }
        return new LinkedHashMap<>(Primitives.getPrimitives(variables));
    }

    public String getQuery() {
        return query;
    }

    public Map<String, Object> getVariables() {
        return variables;
    }

    public String getOperationName() {
        return operationName;
    }
}
