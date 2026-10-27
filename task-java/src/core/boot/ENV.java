package core.boot;

import java.util.Map;

/**
 * Catalogo de constantes de entorno. El COMPORTAMIENTO (getEnvironment/isLocal/isProduction/isDebug/color)
 * vive en EnvironmentReaderRawRepository (Shared/Infrastructure/Repositories/Configuration).
 */
public final class ENV {

    public static final String LOCAL = "local";
    public static final String DEVELOP = "develop";
    public static final String TEST = "test";
    public static final String PRODUCTION = "production";

    public static final Map<String, String> COLORS = Map.of(
        LOCAL, "#fc0ac3", //rosa
        DEVELOP, "#0174ee", //azul
        TEST, "#2f8a01", //verde
        PRODUCTION, ""
    );

    private ENV() {
    }
}
