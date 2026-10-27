/**
 * Claves del .env que la app consume (las que nadie lee no se mapean). Solo las usan
 * EnvironmentReaderRawRepository (un getter semántico por clave) y el loader appboot_loadenv():
 * el resto del código nunca escribe un nombre de variable de entorno.
 * Sin las claves de MySQL/vault/elastic de task-php: alli tambien son codigo muerto.
 */
export const AppEnvKeyEnum = Object.freeze({
    APP_ENV: "APP_ENV",
    APP_DEBUG: "APP_DEBUG",
    APP_DEFAULT_LANG: "APP_DEFAULT_LANG",
    APP_DOMAIN: "APP_DOMAIN",
    APP_SQLITE_PATH: "APP_SQLITE_PATH",
    APP_EMAIL_TO: "APP_EMAIL_TO",
    APP_EMAIL_FROM: "APP_EMAIL_FROM",
    // el sendmail_path del php.ini de task-php: el comando al que FuncMailer entrega el email
    APP_SENDMAIL_PATH: "APP_SENDMAIL_PATH",
    APP_LOG_PATH: "APP_LOG_PATH",
    APP_NAME: "APP_NAME",
    APP_BASE_URL: "APP_BASE_URL",
});
