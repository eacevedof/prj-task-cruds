use std::backtrace::Backtrace;
use std::error::Error;
use std::panic::{self, AssertUnwindSafe};

use crate::console::application::interface_console::CommandConfig;
use crate::core::boot::sapi::set_sapi_name_cli;
use crate::core::config::lgerr;
use crate::modules::shared::infrastructure::components::uuid::uuid_generator::UuidGenerator;
use crate::modules::shared::infrastructure::exceptions::interface_exception::{AppResult, get_panic_error};
use crate::modules::shared::infrastructure::repositories::configuration::environment_reader_raw_repository::EnvironmentReaderRawRepository;

/// el console.php de task-php: ./console <alias> --clave=valor ... -> el command del alias.
/// Sin el modo --class=... de task-php (invocar cualquier clase por reflexion): alli nadie lo usa.
pub struct ConsoleMain {
    argv: Vec<String>,
    command_configs: Vec<CommandConfig>,
}

impl ConsoleMain {
    pub fn get_instance(argv: Vec<String>, command_configs: Vec<CommandConfig>) -> Self {
        set_sapi_name_cli();
        Self { argv, command_configs }
    }

    pub fn exec(&self) {
        let invoked = panic::catch_unwind(AssertUnwindSafe(|| self.invoke_console_service_with_args()));
        let error = match invoked {
            Ok(Ok(())) => return,
            Ok(Err(error)) => error,
            Err(panic_payload) => get_panic_error(&*panic_payload),
        };
        debug_error(&*error);
        on_error(&*error);
    }

    fn invoke_console_service_with_args(&self) -> AppResult<()> {
        let mut alias = self.argv.get(1).map(|alias| alias.trim()).unwrap_or("");
        if alias.is_empty() {
            alias = "help";
        }

        let Some(command_config) = self.command_configs.iter().find(|command_config| command_config.alias == alias)
        else {
            return Err(format!("no class found for cmd {alias}").into());
        };
        //a partir de la pos 2 en adelante son parametros de input
        let input = self.argv.iter().skip(2).cloned().collect();
        (command_config.service)(input).run();
        Ok(())
    }
}

fn on_error(error: &dyn Error) {
    let error_uuid = UuidGenerator::get_instance().get_unique_id_with_prefix("");
    lgerr(&error.to_string(), &format!("console-exception {error_uuid}"), "error");
    lgerr(&Backtrace::force_capture().to_string(), &format!("console-exception {error_uuid} TRACE"), "error");
}

fn debug_error(error: &dyn Error) {
    let environment_reader_raw_repository = EnvironmentReaderRawRepository::get_instance();
    if environment_reader_raw_repository.is_production() || !environment_reader_raw_repository.is_debug() {
        return;
    }
    println!("Exception: {error}\nresponse: 500");
}
