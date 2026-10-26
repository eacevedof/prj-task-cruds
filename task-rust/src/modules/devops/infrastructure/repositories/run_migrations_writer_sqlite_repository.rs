use std::fs;
use std::path::Path;

use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::repositories::abstract_sqlite_repository::{
    AbstractSqliteRepository, SqlParam,
};

pub struct RunMigrationsWriterSqliteRepository {
    abstract_sqlite_repository: AbstractSqliteRepository,
}

extends!(RunMigrationsWriterSqliteRepository, abstract_sqlite_repository, AbstractSqliteRepository);

impl RunMigrationsWriterSqliteRepository {
    pub fn get_instance() -> Self {
        Self { abstract_sqlite_repository: AbstractSqliteRepository::new() }
    }

    /// SQLite ejecuta varias sentencias en un solo execute_batch: el fichero va entero, sin trocearlo por ";".
    pub fn run_sql_file(&self, path_sql_file: &Path) -> AppResult<()> {
        let sql = self.get_file_content_sql(path_sql_file)?;
        if sql.is_empty() {
            return Ok(());
        }
        self.execute_raw(&sql)
    }

    /// fichero + registro en migrations en la MISMA transaccion: si el SQL falla no queda aplicado a medias ni marcado
    /// como ejecutado. Ojo: en SQLite el DDL tambien es transaccional.
    pub fn run_migration_file_and_save(&self, path_sql_file: &Path, migration_file: &str, batch: i64) -> AppResult<()> {
        self.run_in_transaction(|| {
            self.run_sql_file(path_sql_file)?;
            self.save_migration(migration_file, batch)
        })
    }

    fn save_migration(&self, migration_file: &str, batch: i64) -> AppResult<()> {
        let sql = format!(
            "
        -- saveMigration
        INSERT INTO `migrations` (migration_file, batch)
        VALUES (?, {batch})
        "
        );
        self.execute(&sql, &[SqlParam::from(migration_file.to_string())])?;
        Ok(())
    }

    fn get_file_content_sql(&self, path_sql_file: &Path) -> AppResult<String> {
        Ok(fs::read_to_string(path_sql_file)?.trim().to_string())
    }
}
