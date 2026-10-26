/// claves del .env que la app consume (las que nadie lee no se mapean). Solo las usan EnvironmentReaderRawRepository
/// (un getter semantico por clave) y el loader appboot_loadenv(): el resto del codigo nunca escribe un nombre de
/// variable de entorno.
/// Sin las claves de MySQL/vault/elastic de task-php: alli tambien son codigo muerto.
pub struct AppEnvKeyEnum;

impl AppEnvKeyEnum {
    pub const APP_ENV: &'static str = "APP_ENV";
    pub const APP_DEBUG: &'static str = "APP_DEBUG";
    pub const APP_DEFAULT_LANG: &'static str = "APP_DEFAULT_LANG";
    pub const APP_DOMAIN: &'static str = "APP_DOMAIN";
    pub const APP_SQLITE_PATH: &'static str = "APP_SQLITE_PATH";
    pub const APP_EMAIL_TO: &'static str = "APP_EMAIL_TO";
    pub const APP_EMAIL_FROM: &'static str = "APP_EMAIL_FROM";
    pub const APP_SENDMAIL_PATH: &'static str = "APP_SENDMAIL_PATH";
    pub const APP_LOG_PATH: &'static str = "APP_LOG_PATH";
    pub const APP_NAME: &'static str = "APP_NAME";
    pub const APP_BASE_URL: &'static str = "APP_BASE_URL";
}
