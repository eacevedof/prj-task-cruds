use std::sync::atomic::{AtomicBool, Ordering};

/// equivalente a php_sapi_name(): "server" para la API y "cli" para la consola. Lo fija el main de cada binario al
/// arrancar (ConsoleMain llama a set_sapi_name_cli) y es de solo lectura despues.
pub const SAPI_SERVER: &str = "server";
pub const SAPI_CLI: &str = "cli";

static IS_CLI: AtomicBool = AtomicBool::new(false);

pub fn set_sapi_name_cli() {
    IS_CLI.store(true, Ordering::Relaxed);
}

pub fn get_sapi_name() -> &'static str {
    if is_cli() { SAPI_CLI } else { SAPI_SERVER }
}

pub fn is_cli() -> bool {
    IS_CLI.load(Ordering::Relaxed)
}
