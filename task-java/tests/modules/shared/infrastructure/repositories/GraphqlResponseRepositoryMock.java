package modules.shared.infrastructure.repositories;

import java.util.ArrayList;
import java.util.List;
import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.GraphqlEnum;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;
import modules.shared.infrastructure.components.json.JsonSerializer;

/**
 * Lectura de una respuesta GraphQL: {"errors": [{"message", "extensions": {"code", "errors"}}], "data": {...}}
 */
public final class GraphqlResponseRepositoryMock {

    private final Map<String, Object> responseArray;

    public GraphqlResponseRepositoryMock(String responseContent) {
        this.responseArray = Primitives.getPrimitives(JsonSerializer.getInstance().getDecoded(responseContent));
    }

    public static GraphqlResponseRepositoryMock fromHttpResponse(HttpResponder httpResponse) {
        return new GraphqlResponseRepositoryMock(httpResponse.getBody());
    }

    public Map<String, Object> getData() {
        return Primitives.getPrimitives(responseArray.get(GraphqlEnum.KEY_DATA));
    }

    public List<Map<String, Object>> getErrors() {
        return Primitives.getPrimitivesList(responseArray.get(GraphqlEnum.KEY_ERRORS));
    }

    /**
     * Codigo HTTP equivalente REST del primer error (0 si es un error de GraphQL, sin extensions).
     */
    public int getFirstErrorCode() {
        return Primitives.getInt(getFirstErrorExtensions().get(GraphqlEnum.EXTENSION_CODE));
    }

    public List<String> getFirstErrorDetails() {
        List<String> errorDetails = new ArrayList<>();
        if (getFirstErrorExtensions().get(GraphqlEnum.EXTENSION_ERRORS) instanceof List<?> errors) {
            for (Object error : errors) {
                errorDetails.add(String.valueOf(error));
            }
        }
        return errorDetails;
    }

    private Map<String, Object> getFirstErrorExtensions() {
        List<Map<String, Object>> errors = getErrors();
        return errors.isEmpty() ? Primitives.of() : Primitives.getPrimitives(errors.getFirst().get("extensions"));
    }
}
