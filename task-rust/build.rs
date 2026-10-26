// version de rustc para GET /health (el PHP_VERSION de task-php, el runtime.Version() de task-go)
use std::env;
use std::process::Command;

fn main() {
    let rustc = env::var("RUSTC").unwrap_or_else(|_| "rustc".to_string());
    let rustc_version = Command::new(rustc)
        .arg("--version")
        .output()
        .ok()
        .and_then(|output| String::from_utf8(output.stdout).ok())
        .and_then(|version| version.split_whitespace().nth(1).map(str::to_string))
        .unwrap_or_default();
    println!("cargo:rustc-env=RUSTC_VERSION={rustc_version}");
    println!("cargo:rerun-if-changed=build.rs");
}
