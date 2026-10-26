use std::env;
use std::fs;
use std::sync::Once;

use task_rust::core::boot::boot_paths_enum::BOOT_PATHS_ENUM;
use task_rust::core::config::appboot_loadenv;

use crate::tests::modules::shared::infrastructure::repositories::fixtures_raw_repository_mock::FixturesRawRepositoryMock;

/// el arranque de cada binario de test se hace una sola vez, aunque sus tests corran en varios hilos
static BOOTSTRAP: Once = Once::new();

/// el primer paso de cada test que toca la BD: mismo arranque que la API pero contra una BD SQLite PROPIA, que se
/// borra y se recrea (migraciones + fixtures) en cada ejecucion. La BD de desarrollo no se toca.
/// Una BD por binario de test (test_db_name), como task-go hace por paquete.
pub fn run_with_test_db(test_db_name: &str) {
    BOOTSTRAP.call_once(|| {
        let path_sqlite_test =
            BOOT_PATHS_ENUM.PATH_ROOT.join("database").join(format!("task_cruds_test-{test_db_name}.sqlite"));
        load_test_env(&path_sqlite_test.to_string_lossy());

        let _ = fs::remove_file(&path_sqlite_test);
        FixturesRawRepositoryMock::get_instance()
            .create_db_with_fixtures()
            .unwrap_or_else(|error| panic!("fixtures: {error}"));
    });
}

/// el primer paso de los tests que no tocan la BD.
pub fn run_without_db() {
    BOOTSTRAP.call_once(|| {
        let path_sqlite_test = BOOT_PATHS_ENUM.PATH_ROOT.join("database").join("task_cruds_test-nodb.sqlite");
        load_test_env(&path_sqlite_test.to_string_lossy());
    });
}

/// la variable REAL gana sobre el .env (appboot_loadenv), asi que esta ruta manda.
/// APP_EMAIL_TO vacio: los tests que provocan un 500 no mandan emails de alerta.
fn load_test_env(path_sqlite_test: &str) {
    for (key, value) in
        [("APP_SQLITE_PATH", path_sqlite_test), ("APP_ENV", "test"), ("APP_EMAIL_TO", ""), ("APP_DOMAIN", "localhost")]
    {
        // SAFETY: dentro de call_once, antes de que ningun test lea el entorno (todos esperan a este arranque)
        unsafe { env::set_var(key, value) };
    }
    appboot_loadenv().unwrap_or_else(|error| panic!("{error}"));
}
