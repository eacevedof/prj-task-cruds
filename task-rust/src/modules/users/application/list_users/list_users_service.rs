use serde_json::json;

use crate::core::config::tr;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::users::application::list_users::list_users_dto::ListUsersDto;
use crate::modules::users::application::list_users::list_users_result_dto::ListUsersResultDto;
use crate::modules::users::domain::enums::users_message_enum::UsersMessageEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;
use crate::modules::users::infrastructure::repositories::users_reader_sqlite_repository::UsersReaderSqliteRepository;

/// cada token solo ve su propio usuario: la lista tiene como mucho un elemento.
pub struct ListUsersService {
    users_reader_sqlite_repository: UsersReaderSqliteRepository,

    list_users_dto: ListUsersDto,
}

impl ListUsersService {
    pub fn get_instance() -> Self {
        Self {
            users_reader_sqlite_repository: UsersReaderSqliteRepository::get_instance(),
            list_users_dto: ListUsersDto::default(),
        }
    }

    /// error: UsersException (401) o uno inesperado.
    pub fn invoke(&mut self, list_users_dto: ListUsersDto) -> AppResult<ListUsersResultDto> {
        self.list_users_dto = list_users_dto;
        self.fail_if_wrong_input()?;

        let user_entity = self.users_reader_sqlite_repository.get_user_by_id(self.list_users_dto.get_auth_user_id())?;
        let users: Vec<_> = user_entity.iter().map(|user_entity| user_entity.to_primitives()).collect();
        Ok(ListUsersResultDto::from_primitives(&get_primitives(json!({"users": users}))))
    }

    fn fail_if_wrong_input(&self) -> AppResult<()> {
        if self.list_users_dto.get_auth_user_id() < 1 {
            return Err(
                UsersException::unauthorized_custom(tr(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN, &[])).into()
            );
        }
        Ok(())
    }
}
