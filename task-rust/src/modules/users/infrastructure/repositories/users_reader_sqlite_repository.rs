use serde_json::Value;

use crate::modules::shared::application::primitives::{Primitives, to_int, to_string};
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::repositories::abstract_sqlite_repository::{
    AbstractSqliteRepository, SqlParam,
};
use crate::modules::users::domain::entities::user_entity::UserEntity;

pub struct UsersReaderSqliteRepository {
    abstract_sqlite_repository: AbstractSqliteRepository,
}

extends!(UsersReaderSqliteRepository, abstract_sqlite_repository, AbstractSqliteRepository);

impl UsersReaderSqliteRepository {
    pub fn get_instance() -> Self {
        Self { abstract_sqlite_repository: AbstractSqliteRepository::new() }
    }

    pub fn get_user_by_id(&self, user_id: i64) -> AppResult<Option<UserEntity>> {
        let sql = format!(
            "
        -- getUserById
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM `users`
        WHERE 1=1
        AND id = {user_id}
        "
        );
        self.get_first_user_entity(self.query(&sql, &[])?)
    }

    pub fn get_user_by_user_uuid(&self, user_uuid: &str) -> AppResult<Option<UserEntity>> {
        let sql = "
        -- getUserByUserUuid
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM `users`
        WHERE 1=1
        AND user_uuid = ?
        ";
        self.get_first_user_entity(self.query(sql, &[SqlParam::from(user_uuid.to_string())])?)
    }

    /// autenticacion: el token llega en claro y aqui solo se compara su hash (api_token_hash es UNIQUE).
    pub fn get_user_by_api_token_hash(&self, api_token_hash: &str) -> AppResult<Option<UserEntity>> {
        let sql = "
        -- getUserByApiTokenHash
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM `users`
        WHERE 1=1
        AND api_token_hash = ?
        ";
        self.get_first_user_entity(self.query(sql, &[SqlParam::from(api_token_hash.to_string())])?)
    }

    /// login: user_name es unico sin distinguir mayusculas (indice uk_users_user_name COLLATE NOCASE).
    pub fn get_user_by_user_name(&self, user_name: &str) -> AppResult<Option<UserEntity>> {
        let sql = "
        -- getUserByUserName
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM `users`
        WHERE 1=1
        AND user_name = ? COLLATE NOCASE
        ";
        self.get_first_user_entity(self.query(sql, &[SqlParam::from(user_name.to_string())])?)
    }

    /// fuera de UserEntity a proposito: el hash solo lo necesita el login y nunca debe acabar en una respuesta. "" =
    /// cuenta sin password (creada antes de 20261025101838-add-password-hash-to-users.sql).
    pub fn get_password_hash_by_user_id(&self, user_id: i64) -> AppResult<String> {
        let sql = format!(
            "
        -- getPasswordHashByUserId
        SELECT
            password_hash
        FROM `users`
        WHERE 1=1
        AND id = {user_id}
        "
        );
        let rows = self.query(&sql, &[])?;
        Ok(rows.first().map(|row| to_string(row.get("password_hash").unwrap_or(&Value::Null))).unwrap_or_default())
    }

    /// unicidad del user_name en el alta (sin distinguir mayusculas, como el indice).
    pub fn does_user_name_exist(&self, user_name: &str) -> AppResult<bool> {
        let sql = "
        -- doesUserNameExist
        SELECT
            COUNT(*) AS n_rows
        FROM `users`
        WHERE 1=1
        AND user_name = ? COLLATE NOCASE
        ";
        self.get_has_rows(self.query(sql, &[SqlParam::from(user_name.to_string())])?)
    }

    /// unicidad del user_name en la modificacion: el del propio usuario no cuenta como repetido.
    pub fn does_user_name_exist_in_other_user(&self, user_entity: &UserEntity) -> AppResult<bool> {
        let sql = format!(
            "
        -- doesUserNameExistInOtherUser
        SELECT
            COUNT(*) AS n_rows
        FROM `users`
        WHERE 1=1
        AND user_name = ? COLLATE NOCASE
        AND id != {}
        ",
            user_entity.get_id()
        );
        self.get_has_rows(self.query(&sql, &[SqlParam::from(user_entity.get_user_name().to_string())])?)
    }

    /// unicidad del email en el alta (sin distinguir mayusculas: la columna es COLLATE NOCASE).
    pub fn does_user_email_exist(&self, user_email: &str) -> AppResult<bool> {
        let sql = "
        -- doesUserEmailExist
        SELECT
            COUNT(*) AS n_rows
        FROM `users`
        WHERE 1=1
        AND user_email = ?
        ";
        self.get_has_rows(self.query(sql, &[SqlParam::from(user_email.to_string())])?)
    }

    /// unicidad del email en la modificacion: el email del propio usuario no cuenta como repetido.
    pub fn does_user_email_exist_in_other_user(&self, user_entity: &UserEntity) -> AppResult<bool> {
        let sql = format!(
            "
        -- doesUserEmailExistInOtherUser
        SELECT
            COUNT(*) AS n_rows
        FROM `users`
        WHERE 1=1
        AND user_email = ?
        AND id != {}
        ",
            user_entity.get_id()
        );
        self.get_has_rows(self.query(&sql, &[SqlParam::from(user_entity.get_user_email().to_string())])?)
    }

    fn get_first_user_entity(&self, rows: Vec<Primitives>) -> AppResult<Option<UserEntity>> {
        Ok(rows.first().map(UserEntity::from_primitives))
    }

    fn get_has_rows(&self, rows: Vec<Primitives>) -> AppResult<bool> {
        Ok(rows.first().is_some_and(|row| to_int(row.get("n_rows").unwrap_or(&Value::Null)) > 0))
    }
}
