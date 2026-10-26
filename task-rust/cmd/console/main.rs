//! consola de task-rust: el console/console.php de task-php. cargo run --bin console -- help

use std::env;
use std::process;

use task_rust::commands::get_commands;
use task_rust::core::boot::console_main::ConsoleMain;
use task_rust::core::config::appboot_loadenv;

fn main() {
    if let Err(error) = appboot_loadenv() {
        eprintln!("{error}");
        process::exit(2);
    }
    // env::args(): el $argv de PHP, con el alias en la posicion 1
    ConsoleMain::get_instance(env::args().collect(), get_commands()).exec();
}
