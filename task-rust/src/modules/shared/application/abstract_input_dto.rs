use crate::modules::shared::application::primitives::{Primitives, get_trimmed_string};
use crate::modules::shared::domain::enums::language_code_enum::LanguageCodeEnum;

/// metadata de la peticion que viaja con TODO dto de entrada hasta la logica de negocio (ip, navegador, so, zona
/// horaria, idioma). La rellena el controller (HttpRequester::get_metadata), que es quien lee el transporte: el
/// servicio solo ve su dto. Desde CLI o un test llega vacia y el idioma cae en EN.
/// El dto hijo la lleva en un campo y la expone con extends!(Hijo, abstract_input_dto, AbstractInputDto).
#[derive(Debug, Clone, Default)]
pub struct AbstractInputDto {
    user_ip_address: String,
    user_browser: String,
    user_browser_version: String,
    user_os: String,
    user_time_zone: String,
    user_language_code: String,
}

impl AbstractInputDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        let mut user_language_code = get_trimmed_string(primitives, "userLanguageCode");
        if user_language_code.is_empty() {
            user_language_code = LanguageCodeEnum::EN.to_string();
        }
        Self {
            user_ip_address: get_trimmed_string(primitives, "userIpAddress"),
            user_browser: get_trimmed_string(primitives, "userBrowser"),
            user_browser_version: get_trimmed_string(primitives, "userBrowserVersion"),
            user_os: get_trimmed_string(primitives, "userOs"),
            user_time_zone: get_trimmed_string(primitives, "userTimeZone"),
            user_language_code,
        }
    }

    pub fn get_user_ip_address(&self) -> &str {
        &self.user_ip_address
    }

    pub fn get_user_browser(&self) -> &str {
        &self.user_browser
    }

    pub fn get_user_browser_version(&self) -> &str {
        &self.user_browser_version
    }

    pub fn get_user_os(&self) -> &str {
        &self.user_os
    }

    pub fn get_user_time_zone(&self) -> &str {
        &self.user_time_zone
    }

    pub fn get_user_language_code(&self) -> &str {
        &self.user_language_code
    }
}
