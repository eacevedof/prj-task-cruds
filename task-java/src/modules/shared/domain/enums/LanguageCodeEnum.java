package modules.shared.domain.enums;

import java.util.List;

/**
 * Idiomas que entiende la API (subetiqueta primaria de Accept-Language, ISO 639-1).
 */
public final class LanguageCodeEnum {

    public static final String ES = "es";
    public static final String EN = "en";

    public static final List<String> VALUES = List.of(ES, EN);

    private LanguageCodeEnum() {
    }
}
