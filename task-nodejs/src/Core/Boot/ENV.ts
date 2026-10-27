/**
 * Catalogo de constantes de entorno. El COMPORTAMIENTO (getEnvironment/isLocal/isProduction/isDebug/color)
 * vive en EnvironmentReaderRawRepository (Shared/Infrastructure/Repositories/Configuration).
 */
export const ENV = Object.freeze({
    LOCAL: "local",
    DEVELOP: "develop",
    TEST: "test",
    PRODUCTION: "production",

    COLORS: Object.freeze<Record<string, string>>({
        local: "#fc0ac3", //rosa
        develop: "#0174ee", //azul
        test: "#2f8a01", //verde
        production: "",
    }),
});
