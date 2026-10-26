//! registro de comandos de consola (el console/commands.php de task-php).
//!
//! ```text
//! ./console <command-alias> --clave=valor .. --clave=valor
//! Ejemplo:
//!     ./console help
//! ```

use crate::console::application::help_command::HelpCommand;
use crate::console::application::interface_console::CommandConfig;
use crate::modules::devops::infrastructure::commands::run_migrations_command::RunMigrationsCommand;
use crate::modules::users::infrastructure::commands::create_user_command::CreateUserCommand;
use crate::modules::users::infrastructure::commands::delete_user_command::DeleteUserCommand;
use crate::modules::users::infrastructure::commands::get_user_command::GetUserCommand;
use crate::modules::users::infrastructure::commands::regenerate_user_api_token_command::RegenerateUserApiTokenCommand;
use crate::modules::users::infrastructure::commands::update_user_command::UpdateUserCommand;

/// alias => command, que hace (una linea) y los argumentos de un ejemplo. El orden es el del help.
pub fn get_commands() -> Vec<CommandConfig> {
    vec![
        CommandConfig {
            alias: "help",
            service: |input| Box::new(HelpCommand::get_instance(input, get_commands())),
            info: "lista los comandos con un ejemplo de cada uno",
            example: "",
        },
        CommandConfig {
            alias: "run-migrations",
            service: |input| Box::new(RunMigrationsCommand::get_instance(input)),
            info: "ejecuta los .sql pendientes de database/migrations (tabla migrations)",
            example: "",
        },
        // cuenta (users): mismos casos de uso que la API; quien tiene la consola actua como el dueño de la cuenta
        CommandConfig {
            alias: "user-create",
            service: |input| Box::new(CreateUserCommand::get_instance(input)),
            info: "alta de cuenta; imprime su api_token (solo esta vez)",
            example: "--user_name=ana.lopez --user_email=ana@example.com --password=<password>",
        },
        CommandConfig {
            alias: "user-get",
            service: |input| Box::new(GetUserCommand::get_instance(input)),
            info: "detalle de una cuenta",
            example: "--user_uuid=<uuid>",
        },
        CommandConfig {
            alias: "user-update",
            service: |input| Box::new(UpdateUserCommand::get_instance(input)),
            info: "reemplazo completo de nombre y email (los dos obligatorios)",
            example: "--user_uuid=<uuid> --user_name=ana.lopez --user_email=ana@example.com",
        },
        CommandConfig {
            alias: "user-delete",
            service: |input| Box::new(DeleteUserCommand::get_instance(input)),
            info: "borrado fisico de la cuenta y sus tareas, sin confirmacion",
            example: "--user_uuid=<uuid>",
        },
        CommandConfig {
            alias: "user-regenerate-api-token",
            service: |input| Box::new(RegenerateUserApiTokenCommand::get_instance(input)),
            info: "nuevo api_token; el anterior deja de valer",
            example: "--user_uuid=<uuid>",
        },
    ]
}
