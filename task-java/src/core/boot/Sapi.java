package core.boot;

/**
 * Equivalente a php_sapi_name(): "server" para la API y "cli" para la consola. Lo fija ConsoleMain al arrancar
 * (setSapiNameCli) y es de solo lectura despues.
 */
public final class Sapi {

    public static final String SAPI_SERVER = "server";
    public static final String SAPI_CLI = "cli";

    private static volatile String sapiName = SAPI_SERVER;

    private Sapi() {
    }

    public static void setSapiNameCli() {
        sapiName = SAPI_CLI;
    }

    public static String getSapiName() {
        return sapiName;
    }
}
