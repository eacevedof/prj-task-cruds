use serde_json::json;

use crate::core::config::tr;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::users::application::delete_user::delete_user_dto::DeleteUserDto;
use crate::modules::users::application::delete_user::delete_user_result_dto::DeleteUserResultDto;
use crate::modules::users::domain::entities::user_entity::UserEntity;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::enums::users_message_enum::UsersMessageEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;
use crate::modules::users::infrastructure::repositories::users_writer_sqlite_repository::UsersWriterSqliteRepository;

/// borrado fisico. Las tasks del usuario se borran con el (FK ON DELETE CASCADE).
pub struct DeleteUserService {
    users_writer_sqlite_repository: UsersWriterSqliteRepository,

    delete_user_dto: DeleteUserDto,
}

impl DeleteUserService {
    pub fn get_instance() -> Self {
        Self {
            users_writer_sqlite_repository: UsersWriterSqliteRepository::get_instance(),
            delete_user_dto: DeleteUserDto::default(),
        }
    }

    /// error: UsersException (401, 404) o uno inesperado.
    pub fn invoke(&mut self, delete_user_dto: DeleteUserDto) -> AppResult<DeleteUserResultDto> {
        self.delete_user_dto = delete_user_dto;
        self.fail_if_wrong_input()?;

        let affected = self.users_writer_sqlite_repository.delete_user_by_id(&UserEntity::from_primitives(
            &get_primitives(json!({
                UsersKeyEnum::ID: self.delete_user_dto.get_auth_user_id(),
                UsersKeyEnum::USER_UUID: self.delete_user_dto.get_auth_user_uuid(),
            })),
        ))?;

        Ok(DeleteUserResultDto::from_primitives(&get_primitives(json!({
            "userUuid": self.delete_user_dto.get_user_uuid(),
            "affected": affected,
        }))))
    }

    /// solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
    fn fail_if_wrong_input(&self) -> AppResult<()> {
        if self.delete_user_dto.get_auth_user_id() < 1 {
            return Err(
                UsersException::unauthorized_custom(tr(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN, &[])).into()
            );
        }
        let user_uuid = self.delete_user_dto.get_user_uuid();
        if user_uuid != self.delete_user_dto.get_auth_user_uuid() {
            return Err(UsersException::not_found_custom(tr(UsersMessageEnum::USER_X_NOT_FOUND, &[&user_uuid])).into());
        }
        Ok(())
    }
}
