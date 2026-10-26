use std::collections::HashMap;
use std::sync::{Arc, Mutex};
use std::time::Duration;

use parking_lot::ReentrantMutex;
use rusqlite::Connection;

use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::repositories::configuration::environment_reader_raw_repository::EnvironmentReaderRawRepository;

/// la conexion de un fichero SQLite, compartida por todo el proceso. Reentrante: una transaccion la retiene y las
/// sentencias que lanza (en ese mismo hilo) la vuelven a tomar.
pub type SqliteConnection = Arc<ReentrantMutex<Connection>>;

/// una conexion por fichero para todo el proceso (en PHP, un PDO por repositorio y peticion)
static SQLITE_DBS_BY_PATH: Mutex<Option<HashMap<String, SqliteConnection>>> = Mutex::new(None);

pub struct DbFactory;

impl DbFactory {
    pub fn get_instance() -> Self {
        Self
    }

    /// la BD del fichero de APP_SQLITE_PATH. Si el fichero no existe, SQLite lo crea (lo normal en el primer make
    /// migrate). La conexion lleva foreign_keys = ON (el ON DELETE CASCADE de tasks lo necesita). Una sola conexion:
    /// SQLite admite un escritor a la vez y asi nunca responde "database is locked".
    pub fn get_sqlite_pdo_by_env_configuration(&self) -> AppResult<SqliteConnection> {
        let path_sqlite = EnvironmentReaderRawRepository::get_instance().get_sqlite_path();
        if path_sqlite.is_empty() {
            return Err("missing env APP_SQLITE_PATH".into());
        }

        let mut sqlite_dbs_by_path = SQLITE_DBS_BY_PATH.lock().unwrap_or_else(|poisoned| poisoned.into_inner());
        let sqlite_dbs_by_path = sqlite_dbs_by_path.get_or_insert_with(HashMap::new);
        if let Some(sqlite_connection) = sqlite_dbs_by_path.get(&path_sqlite) {
            return Ok(Arc::clone(sqlite_connection));
        }

        let connection = Connection::open(&path_sqlite)?;
        connection.execute_batch("PRAGMA foreign_keys = ON;")?;
        connection.busy_timeout(Duration::from_millis(5000))?;
        let sqlite_connection = Arc::new(ReentrantMutex::new(connection));
        sqlite_dbs_by_path.insert(path_sqlite, Arc::clone(&sqlite_connection));
        Ok(sqlite_connection)
    }

    /// olvida las conexiones abiertas (los tests, antes de borrar el fichero de su BD): se cierran al soltar la ultima
    /// referencia.
    pub fn close_all(&self) {
        let mut sqlite_dbs_by_path = SQLITE_DBS_BY_PATH.lock().unwrap_or_else(|poisoned| poisoned.into_inner());
        *sqlite_dbs_by_path = None;
    }
}
