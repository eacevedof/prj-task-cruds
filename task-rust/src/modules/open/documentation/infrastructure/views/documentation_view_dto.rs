use crate::modules::shared::application::primitives::{Primitives, get_trimmed_string, to_string};

#[derive(Debug, Clone, Default)]
pub struct DocumentationViewDto {
    app_version: String,
    app_version_update: String,
    app_base_url: String,
    errors: Vec<String>,
}

impl DocumentationViewDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        let errors = primitives
            .get("errors")
            .and_then(|errors| errors.as_array())
            .map(|errors| errors.iter().map(to_string).collect())
            .unwrap_or_default();
        Self {
            app_version: get_trimmed_string(primitives, "appVersion"),
            app_version_update: get_trimmed_string(primitives, "appVersionUpdate"),
            app_base_url: get_trimmed_string(primitives, "appBaseUrl"),
            errors,
        }
    }

    pub fn get_app_version(&self) -> &str {
        &self.app_version
    }

    pub fn get_app_version_update(&self) -> &str {
        &self.app_version_update
    }

    pub fn get_app_base_url(&self) -> &str {
        &self.app_base_url
    }

    pub fn get_errors(&self) -> &[String] {
        &self.errors
    }
}
