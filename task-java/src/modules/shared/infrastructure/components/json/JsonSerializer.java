package modules.shared.infrastructure.components.json;

import com.google.gson.FormattingStyle;
import com.google.gson.Gson;
import com.google.gson.GsonBuilder;
import com.google.gson.JsonParseException;
import com.google.gson.ToNumberPolicy;

/**
 * El json_encode / json_decode de PHP (en Java no hay uno en el JDK). Los null se escriben (un campo opcional sale
 * como null, contrato de la API), sin escapar html ni "/" y los numeros enteros se leen como Long.
 * Los objetos JSON se leen como Map con el orden de sus claves, como el array de PHP.
 */
public final class JsonSerializer {

    private static final Gson GSON = getGsonBuilder().create();
    private static final Gson GSON_PRETTY = getGsonBuilder()
        .setFormattingStyle(FormattingStyle.PRETTY.withIndent("    "))
        .create();

    public static JsonSerializer getInstance() {
        return new JsonSerializer();
    }

    public String getJson(Object value) {
        return GSON.toJson(value);
    }

    /**
     * JSON_PRETTY_PRINT: con 4 espacios de sangria.
     */
    public String getPrettyJson(Object value) {
        return GSON_PRETTY.toJson(value);
    }

    /**
     * json_decode($json, true): Map, List, String, Long, Double, Boolean o null. Un JSON mal formado -> null.
     */
    public Object getDecoded(String json) {
        try {
            return GSON.fromJson(json, Object.class);
        }
        catch (JsonParseException e) {
            return null;
        }
    }

    private static GsonBuilder getGsonBuilder() {
        return new GsonBuilder()
            .serializeNulls()
            .disableHtmlEscaping()
            .setObjectToNumberStrategy(ToNumberPolicy.LONG_OR_DOUBLE);
    }
}
