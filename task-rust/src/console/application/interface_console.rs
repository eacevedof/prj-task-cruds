/// lo que tiene que hacer todo comando de consola.
pub trait InterfaceConsole {
    fn run(&self);
}

/// una entrada de console/commands.rs: alias => el command, que hace (una linea) y los argumentos de un ejemplo (el
/// help lo imprime como "console <alias> <example>").
pub struct CommandConfig {
    pub alias: &'static str,
    pub service: fn(Vec<String>) -> Box<dyn InterfaceConsole>,
    pub info: &'static str,
    pub example: &'static str,
}
