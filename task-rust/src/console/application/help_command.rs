use crate::console::application::interface_console::{CommandConfig, InterfaceConsole};
use crate::modules::shared::infrastructure::commands::abstract_console_command::AbstractConsoleCommand;
use crate::modules::shared::infrastructure::traits::cli_color_trait::CliColorTrait;

/// ./console help   (o sin alias)
/// Lista console/commands.rs: por comando, que hace y la llamada tal cual se escribe (el struct no: es detalle
/// interno). En verde el comando, en amarillo la llamada que se copia.
pub struct HelpCommand {
    abstract_console_command: AbstractConsoleCommand,
    command_configs: Vec<CommandConfig>,
}

extends!(HelpCommand, abstract_console_command, AbstractConsoleCommand);

impl HelpCommand {
    pub fn get_instance(input: Vec<String>, command_configs: Vec<CommandConfig>) -> Self {
        Self { abstract_console_command: AbstractConsoleCommand::new(input), command_configs }
    }
}

impl InterfaceConsole for HelpCommand {
    fn run(&self) {
        let mut lines = vec![];
        for (index, command_config) in self.command_configs.iter().enumerate() {
            let info =
                if command_config.info.is_empty() { String::new() } else { format!("\n\t{}", command_config.info) };
            let call = format!("./console {} {}", command_config.alias, command_config.example).trim().to_string();
            lines.push(format!(
                "\n({}) {}:{info}\n\t{}",
                index + 1,
                self.get_green(command_config.alias),
                self.get_yellow(&call)
            ));
        }
        self.echo_blue("help menu");
        println!("{}", lines.join(""));
    }
}
