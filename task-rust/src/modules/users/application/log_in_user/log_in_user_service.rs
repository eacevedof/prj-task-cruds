use serde_json::json;

use crate::core::config::tr;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::infrastructure::components::date::date_timer::DateTimer;
use crate::modules::shared::infrastructure::components::password::password_hasher::PasswordHasher;
use crate::modules::shared::infrastructure::components::token::token_generator::TokenGenerator;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::users::application::log_in_user::log_in_user_dto::LogInUserDto;
use crate::modules::users::application::log_in_user::log_in_user_result_dto::LogInUserResultDto;
use crate::modules::users::domain::entities::user_entity::UserEntity;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::enums::users_message_enum::UsersMessageEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;
use crate::modules::users::infrastructure::repositories::users_reader_sqlite_repository::UsersReaderSqliteRepository;
use crate::modules::users::infrastructure::repositories::users_writer_sqlite_repository::UsersWriterSqliteRepository;

/// login con user_name + password: devuelve un api_token NUEVO y el anterior deja de valer (como regenerar). Usuario
/// inexistente, sin password o password erronea responden lo mismo (401, mismo mensaje y mismo tiempo): no se revela
/// que user_name existen.
pub struct LogInUserService {
    date_timer: DateTimer,
    password_hasher: PasswordHasher,
    token_generator: TokenGenerator,
    users_reader_sqlite_repository: UsersReaderSqliteRepository,
    users_writer_sqlite_repository: UsersWriterSqliteRepository,

    log_in_user_dto: LogInUserDto,
    password_hash: String,
}

impl LogInUserService {
    pub fn get_instance() -> Self {
        Self {
            date_timer: DateTimer::get_instance(),
            password_hasher: PasswordHasher::get_instance(),
            token_generator: TokenGenerator::get_instance(),
            users_reader_sqlite_repository: UsersReaderSqliteRepository::get_instance(),
            users_writer_sqlite_repository: UsersWriterSqliteRepository::get_instance(),
            log_in_user_dto: LogInUserDto::default(),
            password_hash: String::new(),
        }
    }

    /// error: UsersException (401) o uno inesperado.
    pub fn invoke(&mut self, log_in_user_dto: LogInUserDto) -> AppResult<LogInUserResultDto> {
        self.log_in_user_dto = log_in_user_dto;
        self.fail_if_wrong_input()?;

        let user_entity = self.try_to_get_user_by_credentials_or_fail()?;
        let user_id = user_entity.get_id();
        let now = self.date_timer.get_now_dt_into_target_tz();
        self.update_password_hash_if_needs_rehash(user_id, &now)?;

        let api_token = self.token_generator.get_new_token();
        self.users_writer_sqlite_repository.update_api_token_hash_by_id(&UserEntity::from_primitives(
            &get_primitives(json!({
                UsersKeyEnum::ID: user_id,
                UsersKeyEnum::API_TOKEN_HASH: self.token_generator.get_token_hash(&api_token),
                UsersKeyEnum::UPDATED_AT: now,
            })),
        ))?;

        // releido: el login cambia updated_at
        let logged_in_user_entity = self.users_reader_sqlite_repository.get_user_by_id(user_id)?;
        Ok(LogInUserResultDto::from_primitives(&get_primitives(json!({
            "user": UserEntity::to_primitives_or_empty(logged_in_user_entity.as_ref()),
            "apiToken": api_token,
        }))))
    }

    /// sin user_name o sin password no hay nada que comprobar: mismo 401 que unas credenciales erroneas.
    fn fail_if_wrong_input(&self) -> AppResult<()> {
        if self.log_in_user_dto.get_user_name().is_empty() || self.log_in_user_dto.get_password().is_empty() {
            return Err(
                UsersException::unauthorized_custom(tr(UsersMessageEnum::INVALID_USER_NAME_OR_PASSWORD, &[])).into()
            );
        }
        Ok(())
    }

    /// el hash vacio (usuario inexistente o sin password) tambien pasa por la verificacion: mismo tiempo de respuesta
    /// que una password erronea.
    fn try_to_get_user_by_credentials_or_fail(&mut self) -> AppResult<UserEntity> {
        let user_entity =
            self.users_reader_sqlite_repository.get_user_by_user_name(self.log_in_user_dto.get_user_name())?;
        self.password_hash = String::new();
        if let Some(user_entity) = &user_entity {
            self.password_hash =
                self.users_reader_sqlite_repository.get_password_hash_by_user_id(user_entity.get_id())?;
        }
        // sin usuario el hash es "": no puede ser valido, asi que tras esta comprobacion siempre hay usuario
        if !self.password_hasher.is_password_valid(self.log_in_user_dto.get_password(), &self.password_hash) {
            return Err(
                UsersException::unauthorized_custom(tr(UsersMessageEnum::INVALID_USER_NAME_OR_PASSWORD, &[])).into()
            );
        }
        user_entity.ok_or_else(|| {
            UsersException::unauthorized_custom(tr(UsersMessageEnum::INVALID_USER_NAME_OR_PASSWORD, &[])).into()
        })
    }

    fn update_password_hash_if_needs_rehash(&self, user_id: i64, now: &str) -> AppResult<()> {
        if !self.password_hasher.does_password_hash_need_rehash(&self.password_hash) {
            return Ok(());
        }

        let password_hash = self.password_hasher.get_password_hash(self.log_in_user_dto.get_password())?;
        self.users_writer_sqlite_repository.update_password_hash_by_id(&UserEntity::from_primitives(
            &get_primitives(json!({
                UsersKeyEnum::ID: user_id,
                UsersKeyEnum::PASSWORD_HASH: password_hash,
                UsersKeyEnum::UPDATED_AT: now,
            })),
        ))?;
        Ok(())
    }
}
