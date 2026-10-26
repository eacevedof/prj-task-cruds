use crate::modules::shared::application::primitives::{Primitives, get_trimmed_string};

#[derive(Debug, Clone, Default)]
pub struct SendAlertEmailResultDto {
    /// false sin error: APP_EMAIL_TO vacio = alertas por email desactivadas (p.ej. en los tests)
    is_sent: bool,
    email_to: String,
}

impl SendAlertEmailResultDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self {
            is_sent: primitives.get("isSent").and_then(|is_sent| is_sent.as_bool()).unwrap_or(false),
            email_to: get_trimmed_string(primitives, "emailTo"),
        }
    }

    pub fn is_sent(&self) -> bool {
        self.is_sent
    }

    pub fn get_email_to(&self) -> &str {
        &self.email_to
    }
}
