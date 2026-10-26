use std::error::Error;
use std::process;

use serde::Serialize;
use serde_json::Value;
use serde_json::ser::{PrettyFormatter, Serializer};

use crate::core::config::tr;
use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::domain::enums::shared_message_enum::SharedMessageEnum;
use crate::modules::shared::infrastructure::exceptions::interface_exception::InterfaceException;
use crate::modules::shared::infrastructure::traits::cli_color_trait::CliColorTrait;
use crate::modules::shared::infrastructure::traits::log_trait::LogTrait;
use crate::modules::shared::infrastructure::traits::mail_trait::MailTrait;

pub const EXIT_CODE_DOMAIN_ERROR: i32 = 1;
pub const EXIT_CODE_INFRASTRUCTURE_ERROR: i32 = 2;

/// el AbstractApiController de la consola (vault: CLI Commands as Controllers): lee --clave=valor, imprime el
/// ResultDto en JSON por stdout (sin color: se puede pasar a jq) y los errores en rojo por stderr con su codigo de
/// salida.
/// 0 ok · 1 error de dominio (lo que en HTTP es un 4xx) · 2 error de infraestructura (el 500, se loguea).
pub struct AbstractConsoleCommand {
    pub log_trait: LogTrait,
    pub mail_trait: MailTrait,
    /// los argumentos que van tras el alias del comando
    input: Vec<String>,
}

impl CliColorTrait for AbstractConsoleCommand {}

impl AbstractConsoleCommand {
    pub fn new(input: Vec<String>) -> Self {
        let log_trait = LogTrait::new(None);
        Self { mail_trait: MailTrait::new(log_trait.clone()), log_trait, input }
    }

    /// --user_name="Ana Lopez" -> "Ana Lopez". Si no se pasa, "" (el servicio decide si es obligatorio).
    pub fn get_option_value(&self, option_name: &str) -> String {
        let option_prefix = format!("--{option_name}=");
        self.input.iter().find_map(|argument| argument.strip_prefix(&option_prefix)).unwrap_or("").to_string()
    }

    /// JSON_PRETTY_PRINT (4 espacios) sin escapar unicode ni barras.
    pub fn print_result(&self, result: &Value) {
        let mut json_buffer = vec![];
        let mut serializer = Serializer::with_formatter(&mut json_buffer, PrettyFormatter::with_indent(b"    "));
        if result.serialize(&mut serializer).is_ok() {
            println!("{}", String::from_utf8_lossy(&json_buffer));
        }
    }

    pub fn exit_with_domain_error(&self, ex: &dyn InterfaceException) -> ! {
        self.print_errors(ex.get_code(), &[ex.to_string()]);
        process::exit(EXIT_CODE_DOMAIN_ERROR);
    }

    /// como el 500 de la API: se loguea con el metodo que fallo y se avisa por email. A diferencia de la API, quien
    /// tiene la consola si ve el mensaje real (log_debug_cli).
    pub fn exit_with_unexpected_error(&self, error: &dyn Error, log_title: &str) -> ! {
        self.log_trait.log_exception(error, log_title);
        self.log_trait.log_debug_cli(&error.to_string(), log_title);
        self.mail_trait.send_mail_on_throwable(error, log_title);
        self.print_errors(
            ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
            &[tr(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED, &[])],
        );
        process::exit(EXIT_CODE_INFRASTRUCTURE_ERROR);
    }

    /// code el mismo que daria la API (404, 409, 400...), para que consola y HTTP se lean igual.
    fn print_errors(&self, code: i64, errors: &[String]) {
        eprintln!("{}", self.get_red(&format!("Error ({code}):")));
        for error_message in errors {
            eprintln!("{}", self.get_red(&format!("  - {error_message}")));
        }
    }
}
