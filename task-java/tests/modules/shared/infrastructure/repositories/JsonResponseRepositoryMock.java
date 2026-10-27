package modules.shared.infrastructure.repositories;

import java.util.ArrayList;
import java.util.List;
import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;
import modules.shared.infrastructure.components.json.JsonSerializer;

/**
 * Lectura del envelope de la API: {"code": int, "status": bool, "errors": string[], "data": object}
 */
public final class JsonResponseRepositoryMock {

    private Map<String, Object> responseArray;

    public JsonResponseRepositoryMock(String responseContent) {
        this.responseArray = Primitives.getPrimitives(JsonSerializer.getInstance().getDecoded(responseContent));
    }

    public static JsonResponseRepositoryMock fromPrimitives(String responseContent) {
        return new JsonResponseRepositoryMock(responseContent);
    }

    public static JsonResponseRepositoryMock fromHttpResponse(HttpResponder httpResponse) {
        return new JsonResponseRepositoryMock(httpResponse.getBody());
    }

    public int getCode() {
        return Primitives.getInt(responseArray.get("code"));
    }

    public boolean getStatus() {
        return Boolean.TRUE.equals(responseArray.get("status"));
    }

    public List<String> getErrors() {
        List<String> errors = new ArrayList<>();
        if (responseArray.get("errors") instanceof List<?> list) {
            for (Object error : list) {
                errors.add(String.valueOf(error));
            }
        }
        return errors;
    }

    public Map<String, Object> getData() {
        return Primitives.getPrimitives(responseArray.get("data"));
    }

    public void getDebug() {
        getDebug("");
    }

    public void getDebug(String useCase) {
        String debug = useCase.isEmpty() ? "" : "useCase: " + useCase + ", ";
        debug += "code: " + getCode() + ", status: " + getStatus() + ", errors: " + String.join(" | ", getErrors());
        System.err.println(debug);
    }

    public void resetAll() {
        responseArray = Primitives.of();
    }
}
