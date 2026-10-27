package core.boot;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;

import modules.shared.application.Primitives;

public final class SensitiveDataMasker {

    private static final String MASK = "***";

    private static final List<String> SENSITIVE_KEY_PARTS = List.of(
        "password",
        "secret",
        "csrf",
        "token",
        "antibot"
    );

    public static SensitiveDataMasker getInstance() {
        return new SensitiveDataMasker();
    }

    public Map<String, Object> getMaskedArray(Map<String, Object> data) {
        Map<String, Object> masked = new LinkedHashMap<>();
        for (Map.Entry<String, Object> entry : data.entrySet()) {
            if (isSensitiveKey(entry.getKey())) {
                masked.put(entry.getKey(), MASK);
                continue;
            }
            masked.put(
                entry.getKey(),
                entry.getValue() instanceof Map<?, ?> ? getMaskedArray(Primitives.getPrimitives(entry.getValue())) : entry.getValue()
            );
        }
        return masked;
    }

    private boolean isSensitiveKey(String key) {
        String lowerKey = key.toLowerCase(Locale.ROOT);
        return SENSITIVE_KEY_PARTS.stream().anyMatch(lowerKey::contains);
    }
}
