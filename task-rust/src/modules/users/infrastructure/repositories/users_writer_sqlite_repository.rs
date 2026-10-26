use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::repositories::abstract_sqlite_repository::{
    AbstractSqliteRepository, SqlParam,
};
use crate::modules::users::domain::entities::user_entity::UserEntity;

pub struct UsersWriterSqliteRepository {
    abstract_sqlite_repository: AbstractSqliteRepository,
}

extends!(UsersWriterSqliteRepository, abstract_sqlite_repository, AbstractSqliteRepository);

impl UsersWriterSqliteRepository {
    pub fn get_instance() -> Self {
        Self { abstract_sqlite_repository: AbstractSqliteRepository::new() }
    }

    /// en el alta updated_at = created_at.
    pub fn insert_user(&self, user_entity: &UserEntity) -> AppResult<i64> {
        let sql = "
        -- insertUser
        INSERT INTO `users`
            (user_uuid, user_name, user_email, password_hash, api_token_hash, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
        ";
        self.execute(
            sql,
            &[
                SqlParam::from(user_entity.get_user_uuid().to_string()),
                SqlParam::from(user_entity.get_user_name().to_string()),
                SqlParam::from(user_entity.get_user_email().to_string()),
                SqlParam::from(user_entity.get_password_hash().to_string()),
                SqlParam::from(user_entity.get_api_token_hash().to_string()),
                SqlParam::from(user_entity.get_created_at().to_string()),
                SqlParam::from(user_entity.get_created_at().to_string()),
            ],
        )?;
        Ok(self.get_last_insert_id())
    }

    pub fn update_user_by_id(&self, user_entity: &UserEntity) -> AppResult<i64> {
        let sql = format!(
            "
        -- updateUserById
        UPDATE `users`
        SET user_name = ?, user_email = ?, updated_at = ?
        WHERE 1=1
        AND id = {}
        ",
            user_entity.get_id()
        );
        self.execute(
            &sql,
            &[
                SqlParam::from(user_entity.get_user_name().to_string()),
                SqlParam::from(user_entity.get_user_email().to_string()),
                SqlParam::from(user_entity.get_updated_at().to_string()),
            ],
        )
    }

    /// el token anterior deja de valer en el acto: solo existe el hash nuevo.
    pub fn update_api_token_hash_by_id(&self, user_entity: &UserEntity) -> AppResult<i64> {
        let sql = format!(
            "
        -- updateApiTokenHashById
        UPDATE `users`
        SET api_token_hash = ?, updated_at = ?
        WHERE 1=1
        AND id = {}
        ",
            user_entity.get_id()
        );
        self.execute(
            &sql,
            &[
                SqlParam::from(user_entity.get_api_token_hash().to_string()),
                SqlParam::from(user_entity.get_updated_at().to_string()),
            ],
        )
    }

    /// rehash en el login cuando cambia el algoritmo o el coste del hash.
    pub fn update_password_hash_by_id(&self, user_entity: &UserEntity) -> AppResult<i64> {
        let sql = format!(
            "
        -- updatePasswordHashById
        UPDATE `users`
        SET password_hash = ?, updated_at = ?
        WHERE 1=1
        AND id = {}
        ",
            user_entity.get_id()
        );
        self.execute(
            &sql,
            &[
                SqlParam::from(user_entity.get_password_hash().to_string()),
                SqlParam::from(user_entity.get_updated_at().to_string()),
            ],
        )
    }

    /// borra tambien sus tasks (FK ON DELETE CASCADE).
    pub fn delete_user_by_id(&self, user_entity: &UserEntity) -> AppResult<i64> {
        let sql = format!(
            "
        -- deleteUserById
        DELETE FROM `users`
        WHERE 1=1
        AND id = {}
        ",
            user_entity.get_id()
        );
        self.execute(&sql, &[])
    }
}
