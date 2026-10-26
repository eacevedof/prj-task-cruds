use task_rust::extends;
use task_rust::modules::devops::application::run_migrations::run_migrations_dto::RunMigrationsDto;
use task_rust::modules::devops::application::run_migrations::run_migrations_service::RunMigrationsService;
use task_rust::modules::shared::application::primitives::Primitives;
use task_rust::modules::shared::infrastructure::components::password::password_hasher::PasswordHasher;
use task_rust::modules::shared::infrastructure::components::token::token_generator::TokenGenerator;
use task_rust::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use task_rust::modules::shared::infrastructure::repositories::abstract_sqlite_repository::{
    AbstractSqliteRepository, SqlParam,
};

use crate::tests::modules::shared::infrastructure::repositories::tasks_raw_repository_mock::TasksRawRepositoryMock;
use crate::tests::modules::shared::infrastructure::repositories::users_raw_repository_mock::UsersRawRepositoryMock;

const FIXTURES_NOW: &str = "2026-01-01 00:00:00";

/// BD de tests desde cero: las migraciones reales de database/migrations + los fixtures de UsersRawRepositoryMock y
/// TasksRawRepositoryMock. Lo lanza bootstrap::run_with_test_db una vez por binario de test.
pub struct FixturesRawRepositoryMock {
    abstract_sqlite_repository: AbstractSqliteRepository,
}

extends!(FixturesRawRepositoryMock, abstract_sqlite_repository, AbstractSqliteRepository);

impl FixturesRawRepositoryMock {
    pub fn get_instance() -> Self {
        Self { abstract_sqlite_repository: AbstractSqliteRepository::new() }
    }

    pub fn create_db_with_fixtures(&self) -> AppResult<()> {
        RunMigrationsService::get_instance().invoke(RunMigrationsDto::from_primitives(&Primitives::new()))?;
        self.insert_users()?;
        self.insert_tasks()
    }

    fn insert_users(&self) -> AppResult<()> {
        let users = [
            (
                UsersRawRepositoryMock::USER_UUID_OK,
                UsersRawRepositoryMock::USER_NAME_OK,
                UsersRawRepositoryMock::USER_EMAIL_OK,
                UsersRawRepositoryMock::USER_API_TOKEN_OK,
                "",
            ),
            (
                UsersRawRepositoryMock::USER_UUID_TO_UPDATE,
                "luis.test",
                UsersRawRepositoryMock::USER_EMAIL_TO_UPDATE,
                UsersRawRepositoryMock::USER_API_TOKEN_TO_UPDATE,
                "",
            ),
            (
                UsersRawRepositoryMock::USER_UUID_TO_DELETE,
                "eva.test",
                UsersRawRepositoryMock::USER_EMAIL_TO_DELETE,
                UsersRawRepositoryMock::USER_API_TOKEN_TO_DELETE,
                "",
            ),
            (
                UsersRawRepositoryMock::USER_UUID_TO_REGENERATE,
                "rosa.test",
                UsersRawRepositoryMock::USER_EMAIL_TO_REGENERATE,
                UsersRawRepositoryMock::USER_API_TOKEN_TO_REGENERATE,
                "",
            ),
            (
                UsersRawRepositoryMock::USER_UUID_SEARCH,
                "sara.test",
                UsersRawRepositoryMock::USER_EMAIL_SEARCH,
                UsersRawRepositoryMock::USER_API_TOKEN_SEARCH,
                "",
            ),
            (
                UsersRawRepositoryMock::USER_UUID_TO_LOG_IN,
                UsersRawRepositoryMock::USER_NAME_TO_LOG_IN,
                UsersRawRepositoryMock::USER_EMAIL_TO_LOG_IN,
                UsersRawRepositoryMock::USER_API_TOKEN_TO_LOG_IN,
                UsersRawRepositoryMock::USER_PASSWORD_TO_LOG_IN,
            ),
        ];
        let sql = "
        -- insertUsers (fixtures)
        INSERT INTO `users`
            (user_uuid, user_name, user_email, password_hash, api_token_hash, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
        ";
        let password_hasher = PasswordHasher::get_instance();
        let token_generator = TokenGenerator::get_instance();
        for (user_uuid, user_name, user_email, api_token, user_password) in users {
            let password_hash = if user_password.is_empty() {
                SqlParam::Null
            } else {
                SqlParam::from(password_hasher.get_password_hash(user_password)?)
            };
            self.execute(
                sql,
                &[
                    SqlParam::from(user_uuid.to_string()),
                    SqlParam::from(user_name.to_string()),
                    SqlParam::from(user_email.to_string()),
                    password_hash,
                    SqlParam::from(token_generator.get_token_hash(api_token)),
                    SqlParam::from(FIXTURES_NOW.to_string()),
                    SqlParam::from(FIXTURES_NOW.to_string()),
                ],
            )?;
        }
        Ok(())
    }

    fn insert_tasks(&self) -> AppResult<()> {
        let tasks = [
            (TasksRawRepositoryMock::TASK_UUID_OK, TasksRawRepositoryMock::TASK_TITLE_OK),
            (TasksRawRepositoryMock::TASK_UUID_TO_UPDATE, "Llamar al banco"),
            (TasksRawRepositoryMock::TASK_UUID_TO_DELETE, "Tirar la basura"),
            (TasksRawRepositoryMock::TASK_UUID_TO_DELETE_GRAPHQL, "Regar las plantas"),
        ];
        let sql = "
        -- insertTasks (fixtures)
        INSERT INTO `tasks`
            (user_id, task_uuid, task_title, created_at, updated_at)
        VALUES
            ((SELECT id FROM `users` WHERE 1=1 AND user_uuid = ?), ?, ?, ?, ?)
        ";
        for (task_uuid, task_title) in tasks {
            self.execute(
                sql,
                &[
                    SqlParam::from(UsersRawRepositoryMock::USER_UUID_OK.to_string()),
                    SqlParam::from(task_uuid.to_string()),
                    SqlParam::from(task_title.to_string()),
                    SqlParam::from(FIXTURES_NOW.to_string()),
                    SqlParam::from(FIXTURES_NOW.to_string()),
                ],
            )?;
        }
        self.insert_search_tasks()
    }

    /// las de la busqueda paginada: en este orden, asi que id desc = gamma, beta, alpha.
    fn insert_search_tasks(&self) -> AppResult<()> {
        let tasks = [
            (
                TasksRawRepositoryMock::SEARCH_TITLE_ALPHA,
                "pending",
                "work",
                Some(TasksRawRepositoryMock::SEARCH_TARGET_DATE_ALPHA),
                "first",
            ),
            (
                TasksRawRepositoryMock::SEARCH_TITLE_BETA,
                "done",
                "personal",
                Some(TasksRawRepositoryMock::SEARCH_TARGET_DATE_BETA),
                "second",
            ),
            (
                TasksRawRepositoryMock::SEARCH_TITLE_GAMMA,
                "pending",
                "other",
                None,
                TasksRawRepositoryMock::SEARCH_DESCRIPTION_GAMMA,
            ),
        ];
        let sql = "
        -- insertSearchTasks (fixtures)
        INSERT INTO `tasks`
            (user_id, task_uuid, task_title, task_status, task_type, target_date, task_description, created_at, updated_at)
        VALUES
            ((SELECT id FROM `users` WHERE 1=1 AND user_uuid = ?), ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        for (index, (task_title, task_status, task_type, target_date, task_description)) in
            tasks.into_iter().enumerate()
        {
            self.execute(
                sql,
                &[
                    SqlParam::from(UsersRawRepositoryMock::USER_UUID_SEARCH.to_string()),
                    SqlParam::from(format!("eeeeeeee-eeee-4eee-8eee-{index:012}")),
                    SqlParam::from(task_title.to_string()),
                    SqlParam::from(task_status.to_string()),
                    SqlParam::from(task_type.to_string()),
                    SqlParam::from(target_date.map(str::to_string)),
                    SqlParam::from(task_description.to_string()),
                    SqlParam::from(FIXTURES_NOW.to_string()),
                    SqlParam::from(FIXTURES_NOW.to_string()),
                ],
            )?;
        }
        Ok(())
    }
}
