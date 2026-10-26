use serde_json::Value;

use crate::modules::shared::application::primitives::{Primitives, to_int};
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::repositories::abstract_sqlite_repository::{
    AbstractSqliteRepository, SqlParam,
};
use crate::modules::tasks::domain::entities::task_entity::TaskEntity;

/// la mas reciente primero en todos los listados
pub const ORDER_BY_NEWEST: &str = "ORDER BY t.id DESC";

pub struct TasksReaderSqliteRepository {
    abstract_sqlite_repository: AbstractSqliteRepository,
}

extends!(TasksReaderSqliteRepository, abstract_sqlite_repository, AbstractSqliteRepository);

impl TasksReaderSqliteRepository {
    pub fn get_instance() -> Self {
        Self { abstract_sqlite_repository: AbstractSqliteRepository::new() }
    }

    /// una pagina de las tasks del usuario que cumplen los filtros (vacio = sin filtro).
    #[allow(clippy::too_many_arguments)]
    pub fn get_tasks_by_user_id_and_filters(
        &self,
        user_id: i64,
        task_text: &str,
        task_status: &str,
        task_type: &str,
        target_date_from: &str,
        target_date_to: &str,
        limit: i64,
        offset: i64,
    ) -> AppResult<Vec<TaskEntity>> {
        let (filters_sql, filters_params) =
            self.get_filters_sql_and_params(task_text, task_status, task_type, target_date_from, target_date_to);
        let sql = format!(
            "
        -- getTasksByUserIdAndFilters
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM `tasks` t
        INNER JOIN `users` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.user_id = {user_id}
        {filters_sql}
        {ORDER_BY_NEWEST}
        LIMIT {limit} OFFSET {offset}
        "
        );
        let rows = self.query(&sql, &filters_params)?;
        Ok(rows.iter().map(TaskEntity::from_primitives).collect())
    }

    pub fn get_tasks_count_by_user_id_and_filters(
        &self,
        user_id: i64,
        task_text: &str,
        task_status: &str,
        task_type: &str,
        target_date_from: &str,
        target_date_to: &str,
    ) -> AppResult<i64> {
        let (filters_sql, filters_params) =
            self.get_filters_sql_and_params(task_text, task_status, task_type, target_date_from, target_date_to);
        let sql = format!(
            "
        -- getTasksCountByUserIdAndFilters
        SELECT
            COUNT(*) AS n_rows
        FROM `tasks` t
        WHERE 1=1
        AND t.user_id = {user_id}
        {filters_sql}
        "
        );
        let rows = self.query(&sql, &filters_params)?;
        Ok(rows.first().map_or(0, |row| to_int(row.get("n_rows").unwrap_or(&Value::Null))))
    }

    pub fn get_task_by_id(&self, task_id: i64) -> AppResult<Option<TaskEntity>> {
        let sql = format!(
            "
        -- getTaskById
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM `tasks` t
        INNER JOIN `users` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.id = {task_id}
        "
        );
        self.get_first_task_entity(self.query(&sql, &[])?)
    }

    pub fn get_task_by_task_uuid(&self, task_uuid: &str) -> AppResult<Option<TaskEntity>> {
        let sql = "
        -- getTaskByTaskUuid
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM `tasks` t
        INNER JOIN `users` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.task_uuid = ?
        ";
        self.get_first_task_entity(self.query(sql, &[SqlParam::from(task_uuid.to_string())])?)
    }

    /// AND de los filtros que vienen informados, con parametros enlazados (alias t = tasks).
    /// Texto: en titulo o descripcion, sin distinguir mayusculas (LIKE de SQLite) y con %, _ y \ escapados para que
    /// se busquen tal cual. Fechas: sobre target_date, ambos extremos incluidos (NULL no entra).
    fn get_filters_sql_and_params(
        &self,
        task_text: &str,
        task_status: &str,
        task_type: &str,
        target_date_from: &str,
        target_date_to: &str,
    ) -> (String, Vec<SqlParam>) {
        let mut conditions = vec![];
        let mut params = vec![];
        if !task_text.is_empty() {
            let like_text = format!("%{}%", task_text.replace('\\', r"\\").replace('%', r"\%").replace('_', r"\_"));
            conditions
                .push(r"AND (t.task_title LIKE ? ESCAPE '\' OR IFNULL(t.task_description, '') LIKE ? ESCAPE '\')");
            params.push(SqlParam::from(like_text.clone()));
            params.push(SqlParam::from(like_text));
        }
        if !task_status.is_empty() {
            conditions.push("AND t.task_status = ?");
            params.push(SqlParam::from(task_status.to_string()));
        }
        if !task_type.is_empty() {
            conditions.push("AND t.task_type = ?");
            params.push(SqlParam::from(task_type.to_string()));
        }
        if !target_date_from.is_empty() {
            conditions.push("AND t.target_date >= ?");
            params.push(SqlParam::from(target_date_from.to_string()));
        }
        if !target_date_to.is_empty() {
            conditions.push("AND t.target_date <= ?");
            params.push(SqlParam::from(target_date_to.to_string()));
        }
        (conditions.join("\n        "), params)
    }

    fn get_first_task_entity(&self, rows: Vec<Primitives>) -> AppResult<Option<TaskEntity>> {
        Ok(rows.first().map(TaskEntity::from_primitives))
    }
}
