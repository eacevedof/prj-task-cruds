use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::repositories::abstract_sqlite_repository::{
    AbstractSqliteRepository, SqlParam,
};
use crate::modules::tasks::domain::entities::task_entity::TaskEntity;

pub struct TasksWriterSqliteRepository {
    abstract_sqlite_repository: AbstractSqliteRepository,
}

extends!(TasksWriterSqliteRepository, abstract_sqlite_repository, AbstractSqliteRepository);

impl TasksWriterSqliteRepository {
    pub fn get_instance() -> Self {
        Self { abstract_sqlite_repository: AbstractSqliteRepository::new() }
    }

    /// target_date / task_description vacios se guardan como NULL. En el alta updated_at = created_at.
    pub fn insert_task(&self, task_entity: &TaskEntity) -> AppResult<i64> {
        let sql = format!(
            "
        -- insertTask
        INSERT INTO `tasks`
        (user_id, task_uuid, task_title, task_status, task_type, target_date, task_description, created_at, updated_at)
        VALUES
        ({}, ?, ?, ?, ?, ?, ?, ?, ?)
        ",
            task_entity.get_user_id()
        );
        self.execute(
            &sql,
            &[
                SqlParam::from(task_entity.get_task_uuid().to_string()),
                SqlParam::from(task_entity.get_task_title().to_string()),
                SqlParam::from(task_entity.get_task_status().to_string()),
                SqlParam::from(task_entity.get_task_type().to_string()),
                get_null_if_empty(task_entity.get_target_date()),
                get_null_if_empty(task_entity.get_task_description()),
                SqlParam::from(task_entity.get_created_at().to_string()),
                SqlParam::from(task_entity.get_created_at().to_string()),
            ],
        )?;
        Ok(self.get_last_insert_id())
    }

    /// el propietario y created_at no cambian: solo los campos editables y updated_at.
    pub fn update_task_by_id(&self, task_entity: &TaskEntity) -> AppResult<i64> {
        let sql = "
        -- updateTaskById
        UPDATE `tasks`
        SET
            task_title = ?,
            task_status = ?,
            task_type = ?,
            target_date = ?,
            task_description = ?,
            updated_at = ?
        WHERE 1=1
        AND id = ?
        ";
        self.execute(
            sql,
            &[
                SqlParam::from(task_entity.get_task_title().to_string()),
                SqlParam::from(task_entity.get_task_status().to_string()),
                SqlParam::from(task_entity.get_task_type().to_string()),
                get_null_if_empty(task_entity.get_target_date()),
                get_null_if_empty(task_entity.get_task_description()),
                SqlParam::from(task_entity.get_updated_at().to_string()),
                SqlParam::from(task_entity.get_id()),
            ],
        )
    }

    pub fn delete_task_by_id(&self, task_entity: &TaskEntity) -> AppResult<i64> {
        let sql = format!(
            "
        -- deleteTaskById
        DELETE FROM `tasks`
        WHERE 1=1
        AND id = {}
        ",
            task_entity.get_id()
        );
        self.execute(&sql, &[])
    }
}

/// el $valor ?: null de task-php: "" se guarda como NULL.
fn get_null_if_empty(value: &str) -> SqlParam {
    if value.is_empty() { SqlParam::Null } else { SqlParam::from(value.to_string()) }
}
