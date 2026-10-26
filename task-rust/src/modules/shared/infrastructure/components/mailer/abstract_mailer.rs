use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;

/// lo que tiene que hacer todo mailer.
pub trait InterfaceMailer {
    fn send(&mut self) -> AppResult<()>;
}

/// fichero adjunto: path obligatorio; mime y file_name opcionales.
#[derive(Debug, Clone, Default)]
pub struct MailAttachment {
    pub path: String,
    pub mime: String,
    pub file_name: String,
}

/// datos comunes de un email. El envio lo hace cada mailer.
/// Sin pila de errores: si no se puede enviar, send() devuelve el error.
#[derive(Debug, Clone, Default)]
pub struct AbstractMailer {
    pub(crate) headers: Vec<String>,
    pub(crate) email_from: String,
    pub(crate) title_from: String,
    pub(crate) emails_to: Vec<String>,
    pub(crate) emails_cc: Vec<String>,
    pub(crate) emails_bcc: Vec<String>,
    pub(crate) attachments: Vec<MailAttachment>,

    pub(crate) subject: String,
    pub(crate) content: String,
}

impl AbstractMailer {
    pub fn set_subject(&mut self, subject: &str) {
        self.subject = subject.to_string();
    }

    pub fn set_from(&mut self, email: &str) {
        self.email_from = email.to_string();
    }

    pub fn set_title_from(&mut self, title: &str) {
        self.title_from = title.to_string();
    }

    pub fn add_to(&mut self, email: &str) {
        self.emails_to.push(email.to_string());
    }

    pub fn add_cc(&mut self, email: &str) {
        self.emails_cc.push(email.to_string());
    }

    pub fn add_bcc(&mut self, email: &str) {
        self.emails_bcc.push(email.to_string());
    }

    /// html; varias lineas se unen con saltos de linea.
    pub fn set_content(&mut self, content_lines: &[&str]) {
        self.content = content_lines.join("\n");
    }

    pub fn add_attachment(&mut self, attachment: MailAttachment) {
        self.attachments.push(attachment);
    }
}
