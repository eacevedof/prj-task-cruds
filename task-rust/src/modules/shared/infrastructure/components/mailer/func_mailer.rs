use std::fs;
use std::io::Write;
use std::path::Path;
use std::process::{Command, Stdio};

use crate::modules::shared::infrastructure::components::mailer::abstract_mailer::{
    AbstractMailer, InterfaceMailer, MailAttachment,
};
use crate::modules::shared::infrastructure::components::uuid::uuid_generator::UuidGenerator;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::repositories::configuration::environment_reader_raw_repository::EnvironmentReaderRawRepository;

const DEFAULT_SENDMAIL_PATH: &str = "/usr/sbin/sendmail -t -i";

const BASE64_ALPHABET: &[u8; 64] = b"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";

/// el mail() de PHP: html y adjuntos opcionales, entregado al comando de APP_SENDMAIL_PATH (el sendmail_path del
/// php.ini). En local es el sendmail de busybox apuntando a Mailpit (docker/app/Dockerfile).
/// Componente: no captura ni loguea, si no se puede enviar devuelve el error.
#[derive(Debug, Default)]
pub struct FuncMailer {
    abstract_mailer: AbstractMailer,
    boundary: String,
}

extends!(FuncMailer, abstract_mailer, AbstractMailer);

impl InterfaceMailer for FuncMailer {
    fn send(&mut self) -> AppResult<()> {
        if self.email_from.is_empty() || self.emails_to.is_empty() {
            return Err("FuncMailer: email from and at least one email to are required".into());
        }

        self.load_boundary();
        self.load_header_from();
        self.load_header_mime();
        self.load_header_cc();
        self.load_header_bcc();

        let mut content = self.get_multipart() + "\n";
        content += &(self.content.clone() + "\n");
        for attachment in &self.attachments {
            content += &self.get_attachment(attachment);
        }

        self.sendmail(&self.emails_to.join(", "), &self.subject, &content, &self.headers.join("\n"))
    }
}

impl FuncMailer {
    pub fn get_instance() -> Self {
        Self::default()
    }

    /// lo que hace mail(): To y Subject delante de las cabeceras, linea en blanco y el cuerpo, por stdin.
    fn sendmail(&self, to: &str, subject: &str, content: &str, headers: &str) -> AppResult<()> {
        let mut sendmail_path = EnvironmentReaderRawRepository::get_instance().get_sendmail_path();
        if sendmail_path.is_empty() {
            sendmail_path = DEFAULT_SENDMAIL_PATH.to_string();
        }
        let sendmail_command: Vec<&str> = sendmail_path.split_whitespace().collect();

        let message = format!("To: {to}\nSubject: {subject}\n{headers}\n\n{content}");
        let mut child = Command::new(sendmail_command[0])
            .args(&sendmail_command[1..])
            .stdin(Stdio::piped())
            .stdout(Stdio::null())
            .stderr(Stdio::piped())
            .spawn()
            .map_err(|error| format!("FuncMailer: mail() failed. {error}"))?;
        if let Some(mut stdin) = child.stdin.take() {
            stdin.write_all(message.as_bytes())?;
        }
        let output = child.wait_with_output()?;
        if !output.status.success() {
            let stderr = String::from_utf8_lossy(&output.stderr);
            return Err(format!("FuncMailer: mail() failed. {} {}", output.status, stderr.trim()).into());
        }
        Ok(())
    }

    fn load_boundary(&mut self) {
        if !self.attachments.is_empty() {
            self.boundary = UuidGenerator::get_instance().get_uuid_v4().replace('-', "");
        }
    }

    fn load_header_from(&mut self) {
        // nombre entre comillas: sin ellas, lo que va entre parentesis ("app (local)") es un comentario y se pierde
        let title_from = self.title_from.replace('"', "");
        let email_from = self.email_from.clone();
        self.headers.push(format!("From: \"{title_from}\" <{email_from}>"));
        self.headers.push(format!("Return-Path: <{email_from}>"));
        self.headers.push(format!("X-Sender: {email_from}"));
    }

    fn load_header_mime(&mut self) {
        let headers = if self.boundary.is_empty() {
            vec![
                "MIME-Version: 1.0".to_string(),
                "Content-Type: text/html; charset=\"UTF-8\"".to_string(),
                "Content-Transfer-Encoding: 8bit".to_string(),
            ]
        } else {
            vec![
                "MIME-Version: 1.0".to_string(),
                format!("Content-Type: multipart/mixed; boundary=\"{}\"", self.boundary),
                "Content-Transfer-Encoding: 7bit".to_string(),
                "This is a MIME encoded message.".to_string(),
            ]
        };
        self.headers.extend(headers);
    }

    fn load_header_cc(&mut self) {
        if !self.emails_cc.is_empty() {
            let header_cc = format!("cc: {}", self.emails_cc.join(", "));
            self.headers.push(header_cc);
        }
    }

    fn load_header_bcc(&mut self) {
        if !self.emails_bcc.is_empty() {
            let header_bcc = format!("bcc: {}", self.emails_bcc.join(", "));
            self.headers.push(header_bcc);
        }
    }

    fn get_multipart(&self) -> String {
        if self.boundary.is_empty() {
            return String::new();
        }
        [
            format!("--{}", self.boundary),
            "Content-Type: text/html; charset=UTF-8".to_string(),
            "Content-Transfer-Encoding: 8bit".to_string(),
        ]
        .join("\n")
    }

    fn get_attachment(&self, attachment: &MailAttachment) -> String {
        let Ok(content) = fs::read(&attachment.path) else {
            return String::new();
        };
        if content.is_empty() {
            return String::new();
        }

        let mime = if attachment.mime.is_empty() { "application/octet-stream" } else { &attachment.mime };
        let alias = if attachment.file_name.is_empty() {
            Path::new(&attachment.path).file_name().map(|name| name.to_string_lossy().to_string()).unwrap_or_default()
        } else {
            attachment.file_name.clone()
        };

        [
            String::new(),
            format!("--{}", self.boundary),
            format!("Content-Type: {mime}; name=\"{alias}\""),
            "Content-Transfer-Encoding: base64".to_string(),
            "Content-Disposition: attachment; ".to_string(),
            chunk_split(&get_base64_encoded(&content), 76),
            format!("--{}--", self.boundary),
            String::new(),
        ]
        .join("\n")
    }
}

/// base64_encode()
fn get_base64_encoded(bytes: &[u8]) -> String {
    let mut encoded = String::with_capacity(bytes.len().div_ceil(3) * 4);
    for chunk in bytes.chunks(3) {
        let triple = (u32::from(chunk[0]) << 16)
            | (u32::from(*chunk.get(1).unwrap_or(&0)) << 8)
            | u32::from(*chunk.get(2).unwrap_or(&0));
        for position in 0..4 {
            if position <= chunk.len() {
                encoded.push(BASE64_ALPHABET[((triple >> (18 - 6 * position)) & 0x3f) as usize] as char);
            } else {
                encoded.push('=');
            }
        }
    }
    encoded
}

/// chunk_split(): trozos de chunk_length con "\r\n" detras de cada uno.
fn chunk_split(text: &str, chunk_length: usize) -> String {
    text.as_bytes().chunks(chunk_length).map(|chunk| String::from_utf8_lossy(chunk).to_string() + "\r\n").collect()
}
