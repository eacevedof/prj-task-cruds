use std::fs;
use std::path::PathBuf;

use serde_json::{Value, json};

use crate::core::boot::boot_paths_enum::BOOT_PATHS_ENUM;
use crate::modules::devops::application::run_migrations::run_migrations_dto::RunMigrationsDto;
use crate::modules::devops::application::run_migrations::run_migrations_result_dto::RunMigrationsResultDto;
use crate::modules::devops::infrastructure::repositories::run_migrations_reader_sqlite_repository::RunMigrationsReaderSqliteRepository;
use crate::modules::devops::infrastructure::repositories::run_migrations_writer_sqlite_repository::RunMigrationsWriterSqliteRepository;
use crate::modules::shared::application::primitives::{get_primitives, to_string};
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;

pub const MIGRATIONS_TABLE: &str = "migrations";
pub const CREATE_MIGRATIONS_TABLE_FILE: &str = "0000-create-migrations-table.sql";

/// ejecuta, en orden de nombre, los .sql de database/migrations que no esten en la tabla migrations. Todos los
/// pendientes de una ejecucion comparten batch (max(batch) + 1).
/// ./console run-migrations
pub struct RunMigrationsService {
    run_migrations_reader_sqlite_repository: RunMigrationsReaderSqliteRepository,
    run_migrations_writer_sqlite_repository: RunMigrationsWriterSqliteRepository,

    run_migrations_dto: RunMigrationsDto,
    sql_migrations_files: Vec<String>,
    sql_files_to_be_ran: Vec<String>,
    messages: Vec<String>,
}

impl RunMigrationsService {
    pub fn get_instance() -> Self {
        Self {
            run_migrations_reader_sqlite_repository: RunMigrationsReaderSqliteRepository::get_instance(),
            run_migrations_writer_sqlite_repository: RunMigrationsWriterSqliteRepository::get_instance(),
            run_migrations_dto: RunMigrationsDto::default(),
            sql_migrations_files: vec![],
            sql_files_to_be_ran: vec![],
            messages: vec![],
        }
    }

    /// error: solo inesperados (un .sql que falla, la BD).
    pub fn invoke(&mut self, run_migrations_dto: RunMigrationsDto) -> AppResult<RunMigrationsResultDto> {
        self.run_migrations_dto = run_migrations_dto;

        self.create_migrations_table_if_does_not_exist()?;

        self.load_migrations_files()?;
        if self.sql_migrations_files.is_empty() {
            self.add_message("No migrations files found");
            return Ok(self.get_run_migrations_result_dto());
        }

        self.load_files_to_be_ran()?;
        if self.sql_files_to_be_ran.is_empty() {
            self.add_message("No migrations files to be ran");
            return Ok(self.get_run_migrations_result_dto());
        }

        self.run_migrations()?;
        Ok(self.get_run_migrations_result_dto())
    }

    fn create_migrations_table_if_does_not_exist(&mut self) -> AppResult<()> {
        if self.run_migrations_reader_sqlite_repository.does_migrations_table_exist(MIGRATIONS_TABLE)? {
            self.add_message("migrations table wont be created, already exists");
            return Ok(());
        }
        self.add_message("creating migrations table");
        self.run_migrations_writer_sqlite_repository.run_sql_file(&self.get_path_sql_file(CREATE_MIGRATIONS_TABLE_FILE))
    }

    fn load_migrations_files(&mut self) -> AppResult<()> {
        let mut sql_files = vec![];
        for dir_entry in fs::read_dir(&BOOT_PATHS_ENUM.PATH_MIGRATIONS)? {
            let dir_entry = dir_entry?;
            let file_name = dir_entry.file_name().to_string_lossy().to_string();
            if dir_entry.file_type()?.is_dir()
                || !file_name.ends_with(".sql")
                || file_name == CREATE_MIGRATIONS_TABLE_FILE
            {
                continue;
            }
            sql_files.push(file_name);
        }
        sql_files.sort();
        self.sql_migrations_files = sql_files;
        Ok(())
    }

    fn load_files_to_be_ran(&mut self) -> AppResult<()> {
        let ran_files_names: Vec<String> = self
            .run_migrations_reader_sqlite_repository
            .get_ran_migrations_files()?
            .iter()
            .map(|ran_migrations_file| to_string(ran_migrations_file.get("migration_file").unwrap_or(&Value::Null)))
            .collect();

        self.sql_files_to_be_ran =
            self.sql_migrations_files.iter().filter(|sql_file| !ran_files_names.contains(sql_file)).cloned().collect();
        Ok(())
    }

    fn run_migrations(&mut self) -> AppResult<()> {
        let batch = self.run_migrations_reader_sqlite_repository.get_max_batch()? + 1;
        self.add_message(&format!("running migrations batch: {batch}"));
        for sql_file in self.sql_files_to_be_ran.clone() {
            self.add_message(&format!("running migration: {sql_file}"));
            self.run_migrations_writer_sqlite_repository.run_migration_file_and_save(
                &self.get_path_sql_file(&sql_file),
                &sql_file,
                batch,
            )?;
            self.add_message(&format!("saved migration: {sql_file}"));
        }
        Ok(())
    }

    fn get_path_sql_file(&self, sql_file: &str) -> PathBuf {
        BOOT_PATHS_ENUM.PATH_MIGRATIONS.join(sql_file)
    }

    /// traza para el ResultDto: el command la imprime y la deja en el log de debug.
    fn add_message(&mut self, message: &str) {
        self.messages.push(message.to_string());
    }

    fn get_run_migrations_result_dto(&self) -> RunMigrationsResultDto {
        RunMigrationsResultDto::from_primitives(&get_primitives(json!({"messages": self.messages})))
    }
}
