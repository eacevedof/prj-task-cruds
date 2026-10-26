use serde_json::json;

use crate::core::config::tr;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::users::application::get_user::get_user_dto::GetUserDto;
use crate::modules::users::application::get_user::get_user_result_dto::GetUserResultDto;
use crate::modules::users::domain::enums::users_message_enum::UsersMessageEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;
use crate::modules::users::infrastructure::repositories::users_reader_sqlite_repository::UsersReaderSqliteRepository;

pub struct GetUserService {
    users_reader_sqlite_repository: UsersReaderSqliteRepository,

    get_user_dto: GetUserDto,
}

impl GetUserService {
    pub fn get_instance() -> Self {
        Self {
            users_reader_sqlite_repository: UsersReaderSqliteRepository::get_instance(),
            get_user_dto: GetUserDto::default(),
        }
    }

    /// error: UsersException (401, 404) o uno inesperado.
    pub fn invoke(&mut self, get_user_dto: GetUserDto) -> AppResult<GetUserResultDto> {
        self.get_user_dto = get_user_dto;
        self.fail_if_wrong_input()?;

        let Some(user_entity) =
            self.users_reader_sqlite_repository.get_user_by_id(self.get_user_dto.get_auth_user_id())?
        else {
            return Err(UsersException::not_found_custom(tr(
                UsersMessageEnum::USER_X_NOT_FOUND,
                &[&self.get_user_dto.get_user_uuid()],
            ))
            .into());
        };

        Ok(GetUserResultDto::from_primitives(&get_primitives(json!({"user": user_entity.to_primitives()}))))
    }

    /// solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
    fn fail_if_wrong_input(&self) -> AppResult<()> {
        if self.get_user_dto.get_auth_user_id() == 0 {
            return Err(
                UsersException::unauthorized_custom(tr(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN, &[])).into()
            );
        }
        let user_uuid = self.get_user_dto.get_user_uuid();
        if user_uuid != self.get_user_dto.get_auth_user_uuid() {
            return Err(UsersException::not_found_custom(tr(UsersMessageEnum::USER_X_NOT_FOUND, &[&user_uuid])).into());
        }
        Ok(())
    }
}
