use std::env;

use crate::core::boot::env::ENV;
use crate::modules::shared::domain::enums::app_env_key_enum::AppEnvKeyEnum;

/// version publicada en /api/v1: es la del CONTRATO (la misma que task-php). Al subirla, anotar el cambio en
/// changelog.md
pub const APP_VERSION: &str = "2.0.0";
pub const APP_VERSION_UPDATE: &str = "2026-10-25";

/// fuente UNICA de lectura de entorno: un getter semantico por cada clave del .env que la app consume (claves en
/// AppEnvKeyEnum). El .env lo carga appboot_loadenv() (core/config) en el entorno del proceso antes del primer
/// get_instance(); las variables REALES del contenedor/OS ganan sobre el fichero.
pub struct EnvironmentReaderRawRepository;

impl EnvironmentReaderRawRepository {
    pub fn get_instance() -> Self {
        Self
    }

    pub fn get_app_version(&self) -> String {
        APP_VERSION.to_string()
    }

    pub fn get_app_version_update(&self) -> String {
        APP_VERSION_UPDATE.to_string()
    }

    pub fn get_base_url(&self) -> String {
        self.get(AppEnvKeyEnum::APP_BASE_URL).trim_end_matches('/').to_string()
    }

    pub fn get_email_to(&self) -> String {
        self.get(AppEnvKeyEnum::APP_EMAIL_TO)
    }

    pub fn get_email_from(&self) -> String {
        self.get(AppEnvKeyEnum::APP_EMAIL_FROM)
    }

    /// el sendmail_path del php.ini de task-php: el comando al que FuncMailer entrega el email.
    pub fn get_sendmail_path(&self) -> String {
        self.get(AppEnvKeyEnum::APP_SENDMAIL_PATH)
    }

    pub fn get_log_path(&self) -> String {
        self.get(AppEnvKeyEnum::APP_LOG_PATH)
    }

    pub fn get_app_name(&self) -> String {
        self.get(AppEnvKeyEnum::APP_NAME)
    }

    pub fn get_environment(&self) -> String {
        self.get(AppEnvKeyEnum::APP_ENV)
    }

    pub fn is_production(&self) -> bool {
        self.get_environment() == ENV::PRODUCTION
    }

    pub fn is_local(&self) -> bool {
        self.get_environment() == ENV::LOCAL
    }

    pub fn is_dev(&self) -> bool {
        self.get_environment() == ENV::DEVELOP
    }

    pub fn is_test(&self) -> bool {
        self.get_environment() == ENV::TEST
    }

    /// filter_var(FILTER_VALIDATE_BOOLEAN): "1", "true", "on", "yes".
    pub fn is_debug(&self) -> bool {
        matches!(self.get(AppEnvKeyEnum::APP_DEBUG).to_lowercase().as_str(), "1" | "true" | "on" | "yes")
    }

    pub fn get_color(&self) -> String {
        let environment = self.get_environment();
        ENV::COLORS
            .iter()
            .find(|(env_name, _)| *env_name == environment)
            .map(|(_, color)| color.to_string())
            .unwrap_or_default()
    }

    pub fn get_sqlite_path(&self) -> String {
        self.get(AppEnvKeyEnum::APP_SQLITE_PATH)
    }

    pub fn get_default_lang(&self) -> String {
        self.get(AppEnvKeyEnum::APP_DEFAULT_LANG)
    }

    pub fn get_domain(&self) -> String {
        self.get(AppEnvKeyEnum::APP_DOMAIN)
    }

    fn get(&self, key: &str) -> String {
        env::var(key).unwrap_or_default().trim().to_string()
    }
}
