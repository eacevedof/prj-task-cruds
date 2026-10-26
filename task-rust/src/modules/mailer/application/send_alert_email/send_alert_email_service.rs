use serde_json::json;

use crate::modules::mailer::application::send_alert_email::send_alert_email_dto::SendAlertEmailDto;
use crate::modules::mailer::application::send_alert_email::send_alert_email_result_dto::SendAlertEmailResultDto;
use crate::modules::mailer::domain::enums::alert_email_type_enum::AlertEmailTypeEnum;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::infrastructure::components::date::date_timer::DateTimer;
use crate::modules::shared::infrastructure::components::mailer::abstract_mailer::InterfaceMailer;
use crate::modules::shared::infrastructure::components::mailer::func_mailer::FuncMailer;
use crate::modules::shared::infrastructure::components::markdown::markdowner::get_escaped_html;
use crate::modules::shared::infrastructure::components::server::server_identifier::ServerIdentifier;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::repositories::configuration::environment_reader_raw_repository::EnvironmentReaderRawRepository;

/// email de alerta al equipo (error, amenaza de seguridad, warning, notice) a APP_EMAIL_TO. Sin APP_EMAIL_TO no envia
/// (alertas desactivadas). No captura: si el envio falla escala, y quien lo usa (MailTrait) decide que hacer para no
/// tapar el error original.
pub struct SendAlertEmailService {
    date_timer: DateTimer,
    func_mailer: FuncMailer,
    server_identifier: ServerIdentifier,
    environment_reader_raw_repository: EnvironmentReaderRawRepository,

    send_alert_email_dto: SendAlertEmailDto,
}

impl SendAlertEmailService {
    pub fn get_instance() -> Self {
        Self {
            date_timer: DateTimer::get_instance(),
            func_mailer: FuncMailer::get_instance(),
            server_identifier: ServerIdentifier::get_instance(),
            environment_reader_raw_repository: EnvironmentReaderRawRepository::get_instance(),
            send_alert_email_dto: SendAlertEmailDto::default(),
        }
    }

    pub fn invoke(&mut self, send_alert_email_dto: SendAlertEmailDto) -> AppResult<SendAlertEmailResultDto> {
        self.send_alert_email_dto = send_alert_email_dto;
        self.fail_if_wrong_input()?;

        let email_to = self.environment_reader_raw_repository.get_email_to();
        if email_to.is_empty() {
            return Ok(SendAlertEmailResultDto::from_primitives(&get_primitives(json!({
                "isSent": false,
                "emailTo": "",
            }))));
        }

        let environment = self.environment_reader_raw_repository.get_environment();
        let app_name = self.environment_reader_raw_repository.get_app_name();
        let mut email_from = self.environment_reader_raw_repository.get_email_from();
        if email_from.is_empty() {
            email_from = email_to.clone();
        }
        let subject = self.get_subject(&environment);
        let html_body = self.get_html_body();
        self.func_mailer.set_from(&email_from);
        self.func_mailer.set_title_from(&format!("{app_name} ({environment})"));
        self.func_mailer.add_to(&email_to);
        self.func_mailer.set_subject(&subject);
        self.func_mailer.set_content(&[html_body.as_str()]);
        self.func_mailer.send()?;

        Ok(SendAlertEmailResultDto::from_primitives(&get_primitives(json!({
            "isSent": true,
            "emailTo": email_to,
        }))))
    }

    /// la alerta la genera el codigo (MailTrait), no un usuario: un dato mal formado es un fallo de programacion.
    fn fail_if_wrong_input(&self) -> AppResult<()> {
        let alert_type = self.send_alert_email_dto.get_alert_type();
        if AlertEmailTypeEnum::get_value(&AlertEmailTypeEnum::SUBJECT_TAGS, alert_type).is_none() {
            return Err(format!("SendAlertEmail: unknown alert type \"{alert_type}\"").into());
        }
        if self.send_alert_email_dto.get_subject().is_empty() {
            return Err("SendAlertEmail: subject is required".into());
        }
        Ok(())
    }

    fn get_subject(&self, environment: &str) -> String {
        let alert_type = self.send_alert_email_dto.get_alert_type();
        let subject_tag = AlertEmailTypeEnum::get_value(&AlertEmailTypeEnum::SUBJECT_TAGS, alert_type).unwrap_or("");
        let server_info =
            format!("{} ({})", self.server_identifier.get_server_name(), self.server_identifier.get_server_ip());
        format!("{subject_tag} ({environment}) {server_info} - {}", self.send_alert_email_dto.get_subject())
    }

    fn get_html_body(&self) -> String {
        let alert_type = self.send_alert_email_dto.get_alert_type();
        let heading = AlertEmailTypeEnum::get_value(&AlertEmailTypeEnum::HEADINGS, alert_type).unwrap_or("");
        let color = AlertEmailTypeEnum::get_value(&AlertEmailTypeEnum::COLORS, alert_type).unwrap_or("");
        let now = self.date_timer.get_now_dt_into_target_tz();
        let base_url = get_escaped_html(&self.environment_reader_raw_repository.get_base_url());
        let subject = get_escaped_html(self.send_alert_email_dto.get_subject());
        let details = get_escaped_html(self.send_alert_email_dto.get_details());

        format!(
            "
            <h3 style=\"color:{color};\">[{now}] {heading}: {subject}</h3>
            <p><a href=\"{base_url}\" target=\"_blank\">{base_url}</a></p>
            <pre>{details}</pre>
        "
        )
    }
}
