use std::env;
use std::path::{Path, PathBuf};
use std::sync::LazyLock;

/// rutas absolutas de la app. La raiz es la primera carpeta con Cargo.toml subiendo desde el cwd (en el contenedor
/// /app; en un test, la del crate): el equivalente al dirname(__DIR__, 3) de PHP.
#[allow(non_snake_case)]
pub struct BootPathsEnum {
    pub PATH_ROOT: PathBuf,
    pub PATH_PUBLIC: PathBuf,
    pub PATH_SRC: PathBuf,
    pub PATH_DISK_CACHE: PathBuf,
    pub PATH_LOGS: PathBuf,
    pub PATH_CONSOLE: PathBuf,
    pub PATH_MIGRATIONS: PathBuf,
}

pub static BOOT_PATHS_ENUM: LazyLock<BootPathsEnum> = LazyLock::new(|| {
    let path_root = get_path_root();
    BootPathsEnum {
        PATH_PUBLIC: path_root.join("public"),
        PATH_SRC: path_root.join("src"),
        PATH_DISK_CACHE: path_root.join("storage").join("cache"),
        PATH_LOGS: path_root.join("storage").join("logs"),
        PATH_CONSOLE: path_root.join("console"),
        PATH_MIGRATIONS: path_root.join("database").join("migrations"),
        PATH_ROOT: path_root,
    }
});

fn get_path_root() -> PathBuf {
    let Ok(cwd) = env::current_dir() else {
        return PathBuf::from(".");
    };
    let mut dir: &Path = &cwd;
    loop {
        if dir.join("Cargo.toml").is_file() {
            return dir.to_path_buf();
        }
        match dir.parent() {
            Some(parent_dir) => dir = parent_dir,
            None => return cwd,
        }
    }
}
