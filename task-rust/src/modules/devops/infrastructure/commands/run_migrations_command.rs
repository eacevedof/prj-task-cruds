use crate::console::application::interface_console::InterfaceConsole;
use crate::modules::devops::application::run_migrations::run_migrations_dto::RunMigrationsDto;
use crate::modules::devops::application::run_migrations::run_migrations_service::RunMigrationsService;
use crate::modules::shared::application::primitives::Primitives;
use crate::modules::shared::infrastructure::commands::abstract_console_command::AbstractConsoleCommand;

/// ./console run-migrations   (make migrate)
/// Traza start / mensajes / end con log_debug_cli (pantalla con color + debug.log).
/// Si una migracion falla sale con codigo 2: el entrypoint del contenedor (set -e) no arranca la API contra una BD a
/// medio migrar. El detalle del fallo queda en storage/logs (error.log).
pub struct RunMigrationsCommand {
    abstract_console_command: AbstractConsoleCommand,
}

extends!(RunMigrationsCommand, abstract_console_command, AbstractConsoleCommand);

impl RunMigrationsCommand {
    pub fn get_instance(input: Vec<String>) -> Self {
        Self { abstract_console_command: AbstractConsoleCommand::new(input) }
    }
}

impl InterfaceConsole for RunMigrationsCommand {
    fn run(&self) {
        const LOG_TITLE: &str = "RunMigrationsCommand.Run";
        self.log_trait.log_debug_cli("start", LOG_TITLE);
        let run_migrations_result =
            RunMigrationsService::get_instance().invoke(RunMigrationsDto::from_primitives(&Primitives::new()));
        let run_migrations_result_dto = match run_migrations_result {
            Ok(run_migrations_result_dto) => run_migrations_result_dto,
            Err(error) => self.exit_with_unexpected_error(&*error, LOG_TITLE),
        };
        for message in run_migrations_result_dto.get_messages() {
            self.log_trait.log_debug_cli(message, LOG_TITLE);
        }
        self.log_trait.log_debug_cli("end", LOG_TITLE);
    }
}
