use serde_json::json;

use crate::core::config::tr;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::users::application::authenticate_console_user::authenticate_console_user_dto::AuthenticateConsoleUserDto;
use crate::modules::users::application::authenticate_user::authenticate_user_result_dto::AuthenticateUserResultDto;
use crate::modules::users::domain::entities::user_entity::UserEntity;
use crate::modules::users::domain::enums::users_message_enum::UsersMessageEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;
use crate::modules::users::infrastructure::repositories::users_reader_sqlite_repository::UsersReaderSqliteRepository;

/// misma salida que AuthenticateUser para que los casos de uso no distingan si la peticion llega por API o por
/// consola.
pub struct AuthenticateConsoleUserService {
    users_reader_sqlite_repository: UsersReaderSqliteRepository,

    authenticate_console_user_dto: AuthenticateConsoleUserDto,
}

impl AuthenticateConsoleUserService {
    pub fn get_instance() -> Self {
        Self {
            users_reader_sqlite_repository: UsersReaderSqliteRepository::get_instance(),
            authenticate_console_user_dto: AuthenticateConsoleUserDto::default(),
        }
    }

    /// error: UsersException (404) o uno inesperado.
    pub fn invoke(
        &mut self,
        authenticate_console_user_dto: AuthenticateConsoleUserDto,
    ) -> AppResult<AuthenticateUserResultDto> {
        self.authenticate_console_user_dto = authenticate_console_user_dto;
        self.fail_if_wrong_input()?;

        let user_entity = self.try_to_get_user_or_fail()?;

        Ok(AuthenticateUserResultDto::from_primitives(&get_primitives(json!({
            "userId": user_entity.get_id(),
            "userUuid": user_entity.get_user_uuid(),
        }))))
    }

    fn fail_if_wrong_input(&self) -> AppResult<()> {
        if self.authenticate_console_user_dto.get_user_uuid().is_empty() {
            return Err(UsersException::not_found_custom(tr(UsersMessageEnum::USER_X_NOT_FOUND, &[&""])).into());
        }
        Ok(())
    }

    fn try_to_get_user_or_fail(&self) -> AppResult<UserEntity> {
        let user_uuid = self.authenticate_console_user_dto.get_user_uuid();
        let user_entity = self.users_reader_sqlite_repository.get_user_by_user_uuid(user_uuid)?;
        user_entity.ok_or_else(|| {
            UsersException::not_found_custom(tr(UsersMessageEnum::USER_X_NOT_FOUND, &[&user_uuid])).into()
        })
    }
}
