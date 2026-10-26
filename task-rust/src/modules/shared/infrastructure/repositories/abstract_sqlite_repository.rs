use std::cell::Cell;
use std::sync::LazyLock;

use regex::Regex;
use rusqlite::types::ValueRef;
use serde_json::Value;

use crate::modules::shared::application::primitives::{Primitives, to_int};
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::factories::db_factory::{DbFactory, SqliteConnection};
use crate::modules::shared::infrastructure::traits::log_trait::LogTrait;

/// un parametro enlazado (?) de una sentencia: SqlParam::from("texto"), SqlParam::from(12), SqlParam::Null.
pub type SqlParam = rusqlite::types::Value;

static PASSWORD_HASH_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"^\$(2[aby]|argon2)").unwrap());

/// base de los repositorios: query/execute con parametros enlazados y cada sentencia al log de SQL
/// (storage/logs/YYYY-MM-DD-sql.sql) lista para copiar y lanzar.
pub struct AbstractSqliteRepository {
    log_trait: LogTrait,
    sqlite_connection: SqliteConnection,
    last_insert_id: Cell<i64>,
}

impl AbstractSqliteRepository {
    /// sin APP_SQLITE_PATH no hay app: el panic llega al catch_unwind de IndexMain/ConsoleMain (en PHP, la Exception
    /// de DbFactory que nadie captura).
    pub fn new() -> Self {
        let sqlite_connection =
            DbFactory::get_instance().get_sqlite_pdo_by_env_configuration().unwrap_or_else(|error| panic!("{error}"));
        Self { log_trait: LogTrait::new(None), sqlite_connection, last_insert_id: Cell::new(0) }
    }

    /// SELECT con parametros enlazados (?). Devuelve las filas como maps columna => valor.
    pub fn query(&self, sql_query: &str, params: &[SqlParam]) -> AppResult<Vec<Primitives>> {
        self.log_trait.log_sql(&self.get_sql_for_log(sql_query, params), "");
        let connection = self.sqlite_connection.lock();
        let mut statement = connection.prepare(sql_query)?;
        let column_names: Vec<String> = statement.column_names().map(|name| name.to_string()).collect();
        let mut rows = statement.query(rusqlite::params_from_iter(params.iter()))?;

        let mut result = vec![];
        while let Some(row) = rows.next()? {
            let mut primitives = Primitives::new();
            for (index, column_name) in column_names.iter().enumerate() {
                primitives.insert(column_name.clone(), get_json_value(row.get_ref(index)?));
            }
            result.push(primitives);
        }
        Ok(result)
    }

    /// INSERT/UPDATE/DELETE con parametros enlazados. Devuelve las filas afectadas.
    pub fn execute(&self, sql_query: &str, params: &[SqlParam]) -> AppResult<i64> {
        self.log_trait.log_sql(&self.get_sql_for_log(sql_query, params), "");
        let connection = self.sqlite_connection.lock();
        let affected = connection.execute(sql_query, rusqlite::params_from_iter(params.iter()))?;
        self.last_insert_id.set(connection.last_insert_rowid());
        Ok(affected as i64)
    }

    /// varias sentencias de golpe (p.ej. un fichero .sql). Sin parametros: solo SQL de confianza.
    pub fn execute_raw(&self, sql_query: &str) -> AppResult<()> {
        self.log_trait.log_sql(sql_query, "");
        self.sqlite_connection.lock().execute_batch(sql_query)?;
        Ok(())
    }

    /// el id del ultimo INSERT de este repositorio (el lastInsertId() de PDO).
    pub fn get_last_insert_id(&self) -> i64 {
        self.last_insert_id.get()
    }

    /// todo lo que haga run va en una transaccion: si devuelve error se deshace entera. Mientras dura, la conexion
    /// es solo de este hilo.
    pub fn run_in_transaction(&self, run: impl FnOnce() -> AppResult<()>) -> AppResult<()> {
        let connection = self.sqlite_connection.lock();
        connection.execute_batch("BEGIN")?;
        match run() {
            Ok(()) => {
                connection.execute_batch("COMMIT")?;
                Ok(())
            }
            Err(error) => {
                let _ = connection.execute_batch("ROLLBACK");
                Err(error)
            }
        }
    }

    pub fn does_table_exist(&self, table_name: &str) -> AppResult<bool> {
        let sql_query = "
        -- doesTableExist
        SELECT
            COUNT(*) AS n_rows
        FROM `sqlite_master`
        WHERE 1=1
        AND type = 'table'
        AND name = ?
        ";
        let rows = self.query(sql_query, &[SqlParam::from(table_name.to_string())])?;
        Ok(rows.first().is_some_and(|row| to_int(row.get("n_rows").unwrap_or(&Value::Null)) > 0))
    }

    /// SOLO para el log: la query con cada parametro en su sitio (strings entre comillas), para copiarla de sql.sql y
    /// lanzarla tal cual. Nunca se ejecuta: la real va siempre con parametros enlazados.
    /// Asume que no hay "?" dentro de literales del SQL (no los hay en los repositorios).
    /// Los hashes de password (bcrypt $2y$..., argon2 $argon2...) salen como '***': no deben quedar en sql.sql.
    fn get_sql_for_log(&self, sql_query: &str, params: &[SqlParam]) -> String {
        if params.is_empty() {
            return sql_query.to_string();
        }

        let mut params_for_log = params.iter();
        let mut sql_for_log = String::with_capacity(sql_query.len());
        for char in sql_query.chars() {
            // los "?" que sobran (mas que parametros) quedan tal cual
            if char == '?'
                && let Some(param) = params_for_log.next()
            {
                sql_for_log += &get_param_for_log(param);
                continue;
            }
            sql_for_log.push(char);
        }
        sql_for_log
    }
}

impl Default for AbstractSqliteRepository {
    fn default() -> Self {
        Self::new()
    }
}

fn get_param_for_log(param: &SqlParam) -> String {
    match param {
        SqlParam::Null => "NULL".to_string(),
        SqlParam::Integer(integer) => integer.to_string(),
        SqlParam::Real(real) => real.to_string(),
        SqlParam::Text(text) if PASSWORD_HASH_PATTERN.is_match(text) => "'***'".to_string(),
        SqlParam::Text(text) => format!("'{}'", text.replace('\'', "''")),
        SqlParam::Blob(_) => "'<blob>'".to_string(),
    }
}

/// el valor de una columna como lo devolveria PDO: entero, real, texto o null.
fn get_json_value(value_ref: ValueRef<'_>) -> Value {
    match value_ref {
        ValueRef::Null => Value::Null,
        ValueRef::Integer(integer) => Value::from(integer),
        ValueRef::Real(real) => Value::from(real),
        ValueRef::Text(text) | ValueRef::Blob(text) => Value::from(String::from_utf8_lossy(text).to_string()),
    }
}
