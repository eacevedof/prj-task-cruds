use serde_json::Value;

use crate::modules::shared::application::primitives::{Primitives, to_int};
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::repositories::abstract_sqlite_repository::AbstractSqliteRepository;

pub struct RunMigrationsReaderSqliteRepository {
    abstract_sqlite_repository: AbstractSqliteRepository,
}

extends!(RunMigrationsReaderSqliteRepository, abstract_sqlite_repository, AbstractSqliteRepository);

impl RunMigrationsReaderSqliteRepository {
    pub fn get_instance() -> Self {
        Self { abstract_sqlite_repository: AbstractSqliteRepository::new() }
    }

    pub fn does_migrations_table_exist(&self, table_name: &str) -> AppResult<bool> {
        self.does_table_exist(table_name)
    }

    pub fn get_ran_migrations_files(&self) -> AppResult<Vec<Primitives>> {
        let sql = "
        -- getRanMigrationsFiles
        SELECT
            id, migration_file
        FROM `migrations`
        WHERE 1=1
        ORDER BY migration_file
        ";
        self.query(sql, &[])
    }

    pub fn get_max_batch(&self) -> AppResult<i64> {
        let sql = "
        -- getMaxBatch
        SELECT
            MAX(batch) AS max_batch
        FROM `migrations`
        WHERE 1=1
        ";
        let rows = self.query(sql, &[])?;
        Ok(rows.first().map_or(0, |row| to_int(row.get("max_batch").unwrap_or(&Value::Null))))
    }
}
