class ENV:
    """
    Catalogo de constantes de entorno. El COMPORTAMIENTO (getEnvironment/isLocal/isProduction/isDebug/color)
    vive en EnvironmentReaderRawRepository (Shared/Infrastructure/Repositories/Configuration).
    """

    LOCAL = "local"
    DEVELOP = "develop"
    TEST = "test"
    PRODUCTION = "production"

    COLORS = {
        "local": "#fc0ac3",  # rosa
        "develop": "#0174ee",  # azul
        "test": "#2f8a01",  # verde
        "production": "",
    }
