package modules.shared.domain.enums;

/**
 * Respuesta de GET /health.
 */
public final class HealthKeyEnum {

    public static final String APP = "app";
    public static final String JAVA = "java";
    public static final String SQL_DRIVERS = "sql_drivers";
    public static final String APP_NAME = "task-java";

    private HealthKeyEnum() {
    }
}
