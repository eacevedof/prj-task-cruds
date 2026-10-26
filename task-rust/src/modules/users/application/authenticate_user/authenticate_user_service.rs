use serde_json::json;

use crate::core::config::tr;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::infrastructure::components::token::token_generator::TokenGenerator;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::users::application::authenticate_user::authenticate_user_dto::AuthenticateUserDto;
use crate::modules::users::application::authenticate_user::authenticate_user_result_dto::AuthenticateUserResultDto;
use crate::modules::users::domain::entities::user_entity::UserEntity;
use crate::modules::users::domain::enums::users_message_enum::UsersMessageEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;
use crate::modules::users::infrastructure::repositories::users_reader_sqlite_repository::UsersReaderSqliteRepository;

/// Bearer token -> usuario. Mismo error para token ausente y token desconocido: no se da pista de cual fallo.
pub struct AuthenticateUserService {
    token_generator: TokenGenerator,
    users_reader_sqlite_repository: UsersReaderSqliteRepository,

    authenticate_user_dto: AuthenticateUserDto,
}

impl AuthenticateUserService {
    pub fn get_instance() -> Self {
        Self {
            token_generator: TokenGenerator::get_instance(),
            users_reader_sqlite_repository: UsersReaderSqliteRepository::get_instance(),
            authenticate_user_dto: AuthenticateUserDto::default(),
        }
    }

    /// error: UsersException (401) o uno inesperado.
    pub fn invoke(&mut self, authenticate_user_dto: AuthenticateUserDto) -> AppResult<AuthenticateUserResultDto> {
        self.authenticate_user_dto = authenticate_user_dto;
        self.fail_if_wrong_input()?;

        let user_entity = self.try_to_get_user_by_api_token_or_fail()?;

        Ok(AuthenticateUserResultDto::from_primitives(&get_primitives(json!({
            "userId": user_entity.get_id(),
            "userUuid": user_entity.get_user_uuid(),
        }))))
    }

    fn fail_if_wrong_input(&self) -> AppResult<()> {
        if self.authenticate_user_dto.get_api_token().is_empty() {
            return Err(
                UsersException::unauthorized_custom(tr(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN, &[])).into()
            );
        }
        Ok(())
    }

    fn try_to_get_user_by_api_token_or_fail(&self) -> AppResult<UserEntity> {
        let user_entity = self.users_reader_sqlite_repository.get_user_by_api_token_hash(
            &self.token_generator.get_token_hash(self.authenticate_user_dto.get_api_token()),
        )?;
        user_entity.ok_or_else(|| {
            UsersException::unauthorized_custom(tr(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN, &[])).into()
        })
    }
}
