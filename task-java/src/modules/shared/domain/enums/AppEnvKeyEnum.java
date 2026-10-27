package modules.shared.domain.enums;

/**
 * Claves del .env que la app consume (las que nadie lee no se mapean). Solo las usan
 * EnvironmentReaderRawRepository (un getter semántico por clave) y el loader appboot_loadenv():
 * el resto del código nunca escribe un nombre de variable de entorno.
 * Sin las claves de MySQL/vault/elastic de task-php: alli tambien son codigo muerto.
 */
public final class AppEnvKeyEnum {

    public static final String APP_ENV = "APP_ENV";
    public static final String APP_DEBUG = "APP_DEBUG";
    public static final String APP_DEFAULT_LANG = "APP_DEFAULT_LANG";
    public static final String APP_DOMAIN = "APP_DOMAIN";
    public static final String APP_SQLITE_PATH = "APP_SQLITE_PATH";
    public static final String APP_EMAIL_TO = "APP_EMAIL_TO";
    public static final String APP_EMAIL_FROM = "APP_EMAIL_FROM";
    // el sendmail_path del php.ini de task-php: el comando al que FuncMailer entrega el email
    public static final String APP_SENDMAIL_PATH = "APP_SENDMAIL_PATH";
    public static final String APP_LOG_PATH = "APP_LOG_PATH";
    public static final String APP_NAME = "APP_NAME";
    public static final String APP_BASE_URL = "APP_BASE_URL";

    private AppEnvKeyEnum() {
    }
}
