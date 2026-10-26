use crate::modules::shared::application::primitives::{Primitives, get_trimmed_string};

#[derive(Debug, Clone, Default)]
pub struct SendAlertEmailDto {
    alert_type: String,
    subject: String,
    details: String,
}

impl SendAlertEmailDto {
    /// alertType (AlertEmailTypeEnum), subject, details (texto plano: el servicio lo escapa)
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self {
            alert_type: get_trimmed_string(primitives, "alertType"),
            subject: get_trimmed_string(primitives, "subject"),
            details: get_trimmed_string(primitives, "details"),
        }
    }

    pub fn get_alert_type(&self) -> &str {
        &self.alert_type
    }

    pub fn get_subject(&self) -> &str {
        &self.subject
    }

    pub fn get_details(&self) -> &str {
        &self.details
    }
}
