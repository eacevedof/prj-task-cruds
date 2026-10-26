use serde_json::json;

use crate::core::config::tr;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::infrastructure::components::date::date_timer::DateTimer;
use crate::modules::shared::infrastructure::components::token::token_generator::TokenGenerator;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::users::application::regenerate_user_api_token::regenerate_user_api_token_dto::RegenerateUserApiTokenDto;
use crate::modules::users::application::regenerate_user_api_token::regenerate_user_api_token_result_dto::RegenerateUserApiTokenResultDto;
use crate::modules::users::domain::entities::user_entity::UserEntity;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::enums::users_message_enum::UsersMessageEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;
use crate::modules::users::infrastructure::repositories::users_writer_sqlite_repository::UsersWriterSqliteRepository;

/// rota el token del propio usuario (p.ej. si se ha filtrado): el anterior deja de valer y el nuevo se devuelve en
/// claro solo en esta respuesta.
pub struct RegenerateUserApiTokenService {
    date_timer: DateTimer,
    token_generator: TokenGenerator,
    users_writer_sqlite_repository: UsersWriterSqliteRepository,

    regenerate_user_api_token_dto: RegenerateUserApiTokenDto,
}

impl RegenerateUserApiTokenService {
    pub fn get_instance() -> Self {
        Self {
            date_timer: DateTimer::get_instance(),
            token_generator: TokenGenerator::get_instance(),
            users_writer_sqlite_repository: UsersWriterSqliteRepository::get_instance(),
            regenerate_user_api_token_dto: RegenerateUserApiTokenDto::default(),
        }
    }

    /// error: UsersException (401, 404) o uno inesperado.
    pub fn invoke(
        &mut self,
        regenerate_user_api_token_dto: RegenerateUserApiTokenDto,
    ) -> AppResult<RegenerateUserApiTokenResultDto> {
        self.regenerate_user_api_token_dto = regenerate_user_api_token_dto;
        self.fail_if_wrong_input()?;

        let api_token = self.token_generator.get_new_token();
        self.users_writer_sqlite_repository.update_api_token_hash_by_id(&UserEntity::from_primitives(
            &get_primitives(json!({
                UsersKeyEnum::ID: self.regenerate_user_api_token_dto.get_auth_user_id(),
                UsersKeyEnum::API_TOKEN_HASH: self.token_generator.get_token_hash(&api_token),
                UsersKeyEnum::UPDATED_AT: self.date_timer.get_now_dt_into_target_tz(),
            })),
        ))?;

        Ok(RegenerateUserApiTokenResultDto::from_primitives(&get_primitives(json!({
            "userUuid": self.regenerate_user_api_token_dto.get_user_uuid(),
            "apiToken": api_token,
        }))))
    }

    /// solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
    fn fail_if_wrong_input(&self) -> AppResult<()> {
        if self.regenerate_user_api_token_dto.get_auth_user_id() < 1 {
            return Err(
                UsersException::unauthorized_custom(tr(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN, &[])).into()
            );
        }
        let user_uuid = self.regenerate_user_api_token_dto.get_user_uuid();
        if user_uuid != self.regenerate_user_api_token_dto.get_auth_user_uuid() {
            return Err(UsersException::not_found_custom(tr(UsersMessageEnum::USER_X_NOT_FOUND, &[&user_uuid])).into());
        }
        Ok(())
    }
}
