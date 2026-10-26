use std::backtrace::Backtrace;
use std::error::Error;
use std::sync::Arc;

use chrono::Utc;

use crate::core::boot::boot_paths_enum::BOOT_PATHS_ENUM;
use crate::core::boot::sapi::is_cli;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::components::logger::logger::Logger;
use crate::modules::shared::infrastructure::components::server::server_identifier::ServerIdentifier;

/// logs de la app en storage/logs/YYYY-MM-DD-<nivel>.log.
/// Cada entrada lleva la ip de quien la provoca (cliente HTTP, o "cli-<ip>" desde consola) y su nivel. La ip del
/// cliente sale del HttpRequester de la peticion: quien no lo tiene (un repositorio) loguea "unknown" en la API.
#[derive(Debug, Clone, Default)]
pub struct LogTrait {
    http_requester: Option<Arc<HttpRequester>>,
}

impl LogTrait {
    pub fn new(http_requester: Option<Arc<HttpRequester>>) -> Self {
        Self { http_requester }
    }

    pub fn log_debug(&self, content: &str, title: &str) {
        self.write_level_log("debug", "DEBUG", content, title);
    }

    pub fn log_info(&self, content: &str, title: &str) {
        self.write_level_log("info", "INFO", content, title);
    }

    pub fn log_sql(&self, sql: &str, title: &str) {
        self.write_level_log("sql", "SQL", sql, title);
    }

    pub fn log_warning(&self, content: &str, title: &str) {
        self.write_level_log("warning", "WARNING", content, title);
    }

    pub fn log_error(&self, content: &str, title: &str) {
        self.write_level_log("error", "ERROR", content, title);
    }

    /// lo que va en todo error inesperado: tipo (su Debug), mensaje y traza.
    pub fn log_exception(&self, error: &dyn Error, title: &str) {
        let title = if title.is_empty() { "ERROR" } else { title };
        let content = [
            format!("ex type:\n\t{error:?}"),
            format!("ex message:\n\t{error}"),
            format!("ex trace:\n\t{}", Backtrace::force_capture()),
        ]
        .join("\n");
        self.write_level_log("error", "ERROR", &content, title);
    }

    /// solo en consola: al debug.log y ademas a pantalla con color (fecha, titulo, contenido).
    pub fn log_debug_cli(&self, content: &str, title: &str) {
        if !is_cli() {
            return;
        }

        let now = Utc::now().format("%Y-%m-%d %H:%M:%S").to_string();
        Logger::get_instance().write_log("debug", &format!("[{now}][{title}]\n{content}"), &BOOT_PATHS_ENUM.PATH_LOGS);
        println!("\x1b[97m[{now}]\x1b[0m\x1b[93m[{title}]\x1b[0m\n\x1b[92m{content}\x1b[0m");
    }

    fn write_level_log(&self, file_name: &str, level: &str, content: &str, title: &str) {
        // en task-php es ["host_ip" => ip] y el implode se queda solo con el valor
        let mut lines = vec![self.get_remote_client_ip()];
        if !title.is_empty() {
            lines.push(title.to_string());
        }
        lines.push(content.to_string());

        Logger::get_instance().write_log(
            file_name,
            &format!("[{level}] {}", lines.join("\n")),
            &BOOT_PATHS_ENUM.PATH_LOGS,
        );
    }

    /// la misma ip que llega a los dtos (HttpRequester::get_client_ip).
    fn get_remote_client_ip(&self) -> String {
        if is_cli() {
            return format!("cli-{}", ServerIdentifier::get_instance().get_server_ip());
        }
        match &self.http_requester {
            Some(http_requester) => {
                let client_ip = http_requester.get_client_ip();
                if client_ip.is_empty() { "unknown".to_string() } else { client_ip }
            }
            None => "unknown".to_string(),
        }
    }
}
