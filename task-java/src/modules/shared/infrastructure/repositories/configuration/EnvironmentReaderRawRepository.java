package modules.shared.infrastructure.repositories.configuration;

import java.util.List;
import java.util.Objects;

import core.boot.ENV;
import core.config.Global;

import modules.shared.domain.enums.AppEnvKeyEnum;

/**
 * Fuente ÚNICA de lectura de entorno: un getter semántico por cada clave del .env que la app consume
 * (claves en AppEnvKeyEnum; las que nadie lee no se mapean). Patrón getInstance singleton. El .env lo carga
 * appboot_loadenv() (core/config/Global) antes del primer getInstance(); las vars REALES del contenedor/OS ganan
 * sobre el fichero.
 */
public final class EnvironmentReaderRawRepository {

    // version publicada en /api/v1: es la del CONTRATO (la misma que task-php). Al subirla, anotar el cambio en
    // changelog.md
    private static final String APP_VERSION = "2.0.1";
    private static final String APP_VERSION_UPDATE = "2026-10-29";

    private static final EnvironmentReaderRawRepository INSTANCE = new EnvironmentReaderRawRepository();

    private EnvironmentReaderRawRepository() {
    }

    public static EnvironmentReaderRawRepository getInstance() {
        return INSTANCE;
    }

    public String getAppVersion() {
        return APP_VERSION;
    }

    public String getAppVersionUpdate() {
        return APP_VERSION_UPDATE;
    }

    public String getBaseUrl() {
        return get(AppEnvKeyEnum.APP_BASE_URL).replaceAll("/+$", "");
    }

    public String getEmailTo() {
        return get(AppEnvKeyEnum.APP_EMAIL_TO);
    }

    public String getEmailFrom() {
        return get(AppEnvKeyEnum.APP_EMAIL_FROM);
    }

    /**
     * El sendmail_path del php.ini de task-php: el comando al que FuncMailer entrega el email.
     */
    public String getSendmailPath() {
        return get(AppEnvKeyEnum.APP_SENDMAIL_PATH);
    }

    public String getLogPath() {
        return get(AppEnvKeyEnum.APP_LOG_PATH);
    }

    public String getAppName() {
        return get(AppEnvKeyEnum.APP_NAME);
    }

    public String getEnvironment() {
        return get(AppEnvKeyEnum.APP_ENV);
    }

    public boolean isProduction() {
        return getEnvironment().equals(ENV.PRODUCTION);
    }

    /**
     * filter_var(FILTER_VALIDATE_BOOLEAN): "1", "true", "on", "yes".
     */
    public boolean isDebug() {
        return List.of("1", "true", "on", "yes").contains(get(AppEnvKeyEnum.APP_DEBUG).toLowerCase());
    }

    public boolean isLocal() {
        return getEnvironment().equals(ENV.LOCAL);
    }

    public String getSqlitePath() {
        return get(AppEnvKeyEnum.APP_SQLITE_PATH);
    }

    public boolean isDev() {
        return getEnvironment().equals(ENV.DEVELOP);
    }

    public boolean isTest() {
        return getEnvironment().equals(ENV.TEST);
    }

    public String getColor() {
        return ENV.COLORS.getOrDefault(getEnvironment(), "");
    }

    public String getDefaultLang() {
        return get(AppEnvKeyEnum.APP_DEFAULT_LANG);
    }

    public String getDomain() {
        return get(AppEnvKeyEnum.APP_DOMAIN);
    }

    /**
     * Se lee en cada llamada: los tests fijan sus variables antes del primer uso y nada se queda con un valor viejo.
     */
    private String get(String key) {
        return Objects.requireNonNullElse(Global.getenv(key), "").trim();
    }
}
