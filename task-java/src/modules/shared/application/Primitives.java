package modules.shared.application;

import java.math.BigDecimal;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * El array asociativo de PHP es un Map<String, Object> con orden de insercion (LinkedHashMap): lo que recibe un dto
 * (fromPrimitives) y lo que devuelven toPrimitives() y toArray(). Aqui los casts de PHP que hacen los dto.
 */
public final class Primitives {

    private static final Pattern LEADING_INT_PATTERN = Pattern.compile("^[+-]?\\d+");

    private Primitives() {
    }

    /**
     * ["clave" => valor, ...]: claves y valores alternos, en orden. Admite valores null (Map.of no).
     */
    public static Map<String, Object> of(Object... keysAndValues) {
        Map<String, Object> primitives = new LinkedHashMap<>();
        for (int i = 0; i + 1 < keysAndValues.length; i += 2) {
            primitives.put(String.valueOf(keysAndValues[i]), keysAndValues[i + 1]);
        }
        return primitives;
    }

    /**
     * [...$a, ...$b]: si una clave se repite, gana la del ultimo.
     */
    @SafeVarargs
    public static Map<String, Object> merge(Map<String, ?>... primitivesList) {
        Map<String, Object> merged = new LinkedHashMap<>();
        for (Map<String, ?> primitives : primitivesList) {
            merged.putAll(primitives);
        }
        return merged;
    }

    /**
     * $primitives["key"] ?? $defaultValue: ausente o null -> el valor por defecto.
     */
    public static Object getValueOrDefault(Map<String, ?> primitives, String key, Object defaultValue) {
        Object value = primitives.get(key);
        return value == null ? defaultValue : value;
    }

    /**
     * trim((string) $value): null, false, un array o un objeto -> "", true -> "1", un numero -> su texto.
     */
    public static String getTrimmedString(Object value) {
        if (value instanceof String string) return string.trim();
        if (value instanceof Double || value instanceof Float) {
            return new BigDecimal(value.toString()).stripTrailingZeros().toPlainString();
        }
        if (value instanceof Number number) return number.toString();
        if (Boolean.TRUE.equals(value)) return "1";
        return "";
    }

    /**
     * (int) $value: "12" -> 12, "12abc" -> 12, "abc" -> 0, 3.9 -> 3, true -> 1, null -> 0.
     */
    public static int getInt(Object value) {
        if (value instanceof Number number) return (int) number.longValue();
        if (value instanceof Boolean bool) return bool ? 1 : 0;
        if (!(value instanceof String string)) return 0;

        Matcher matcher = LEADING_INT_PATTERN.matcher(string.trim());
        if (!matcher.find()) return 0;
        try {
            return Integer.parseInt(matcher.group());
        }
        catch (NumberFormatException e) {
            return 0;
        }
    }

    /**
     * $primitives["key"] ?? []: el array anidado de un array de primitivos (un objeto JSON), o vacio.
     */
    @SuppressWarnings("unchecked")
    public static Map<String, Object> getPrimitives(Object value) {
        return value instanceof Map<?, ?> map ? (Map<String, Object>) map : new LinkedHashMap<>();
    }

    /**
     * Una lista de arrays de primitivos (lista de toPrimitives), o vacia.
     */
    public static List<Map<String, Object>> getPrimitivesList(Object value) {
        List<Map<String, Object>> primitivesList = new ArrayList<>();
        if (!(value instanceof List<?> list)) return primitivesList;

        for (Object item : list) {
            primitivesList.add(getPrimitives(item));
        }
        return primitivesList;
    }
}
