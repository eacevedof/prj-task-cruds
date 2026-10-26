use std::backtrace::Backtrace;
use std::error::Error;

use serde_json::json;

use crate::modules::mailer::application::send_alert_email::send_alert_email_dto::SendAlertEmailDto;
use crate::modules::mailer::application::send_alert_email::send_alert_email_service::SendAlertEmailService;
use crate::modules::mailer::domain::enums::alert_email_type_enum::AlertEmailTypeEnum;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::infrastructure::traits::log_trait::LogTrait;

/// emails de alerta desde un controller o un command. Se llama desde su rama de error: por eso aqui SI se captura, un
/// fallo al enviar se loguea y nunca tapa el error original. Necesita un LogTrait para ese log.
#[derive(Debug, Clone, Default)]
pub struct MailTrait {
    log_trait: LogTrait,
}

impl MailTrait {
    pub fn new(log_trait: LogTrait) -> Self {
        Self { log_trait }
    }

    pub fn send_mail_on_throwable(&self, error: &dyn Error, title: &str) {
        let details = [
            format!("{error:?}: {error}"),
            String::new(),
            "Trace:".to_string(),
            Backtrace::force_capture().to_string(),
        ]
        .join("\n");
        self.send_alert_email(AlertEmailTypeEnum::ERROR, title, &details);
    }

    pub fn send_email_on_security_threat(&self, message: &str, subject: &str) {
        self.send_alert_email(AlertEmailTypeEnum::SECURITY_THREAT, subject, message);
    }

    pub fn send_email_warning(&self, message: &str, subject: &str) {
        self.send_alert_email(AlertEmailTypeEnum::WARNING, subject, message);
    }

    pub fn send_email_notice(&self, message: &str, subject: &str) {
        self.send_alert_email(AlertEmailTypeEnum::NOTICE, subject, message);
    }

    fn send_alert_email(&self, alert_type: &str, subject: &str, details: &str) {
        let send_alert_email_result =
            SendAlertEmailService::get_instance().invoke(SendAlertEmailDto::from_primitives(&get_primitives(json!({
                "alertType": alert_type,
                "subject": subject,
                "details": details,
            }))));
        if let Err(error) = send_alert_email_result {
            self.log_trait.log_exception(&*error, "MailTrait.sendAlertEmail");
        }
    }
}
