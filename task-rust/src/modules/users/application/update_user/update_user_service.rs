use serde_json::json;

use crate::core::config::tr;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::domain::enums::shared_message_enum::SharedMessageEnum;
use crate::modules::shared::infrastructure::components::date::date_timer::DateTimer;
use crate::modules::shared::infrastructure::exceptions::fields_exception::FieldsException;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::traits::error_stack_trait::ErrorStackTrait;
use crate::modules::users::application::update_user::update_user_dto::UpdateUserDto;
use crate::modules::users::application::update_user::update_user_result_dto::UpdateUserResultDto;
use crate::modules::users::domain::entities::user_entity::UserEntity;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::enums::users_message_enum::UsersMessageEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;
use crate::modules::users::domain::services::user_fields_validator::UserFieldsValidator;
use crate::modules::users::infrastructure::repositories::users_reader_sqlite_repository::UsersReaderSqliteRepository;
use crate::modules::users::infrastructure::repositories::users_writer_sqlite_repository::UsersWriterSqliteRepository;

/// PUT: reemplazo completo, user_name y user_email obligatorios (mismas reglas que el alta).
pub struct UpdateUserService {
    date_timer: DateTimer,
    users_reader_sqlite_repository: UsersReaderSqliteRepository,
    users_writer_sqlite_repository: UsersWriterSqliteRepository,

    update_user_dto: UpdateUserDto,
    user_entity: UserEntity,
}

impl ErrorStackTrait for UpdateUserService {}

impl UpdateUserService {
    pub fn get_instance() -> Self {
        Self {
            date_timer: DateTimer::get_instance(),
            users_reader_sqlite_repository: UsersReaderSqliteRepository::get_instance(),
            users_writer_sqlite_repository: UsersWriterSqliteRepository::get_instance(),
            update_user_dto: UpdateUserDto::default(),
            user_entity: UserEntity::default(),
        }
    }

    /// error: FieldsException (400), UsersException (401, 404, 409) o uno inesperado.
    pub fn invoke(&mut self, update_user_dto: UpdateUserDto) -> AppResult<UpdateUserResultDto> {
        self.update_user_dto = update_user_dto;
        self.fail_if_wrong_input()?;
        self.fail_if_wrong_fields()?;

        // autenticado = existe: su id ya viene resuelto
        self.user_entity = UserEntity::from_primitives(&get_primitives(json!({
            UsersKeyEnum::ID: self.update_user_dto.get_auth_user_id(),
            UsersKeyEnum::USER_UUID: self.update_user_dto.get_auth_user_uuid(),
            UsersKeyEnum::USER_NAME: self.update_user_dto.get_user_name(),
            UsersKeyEnum::USER_EMAIL: self.update_user_dto.get_user_email(),
            UsersKeyEnum::UPDATED_AT: self.date_timer.get_now_dt_into_target_tz(),
        })));
        self.fail_if_user_name_or_email_exists_in_other_user()?;

        self.users_writer_sqlite_repository.update_user_by_id(&self.user_entity)?;

        let user_entity = self.users_reader_sqlite_repository.get_user_by_id(self.user_entity.get_id())?;
        Ok(UpdateUserResultDto::from_primitives(&get_primitives(json!({
            "user": UserEntity::to_primitives_or_empty(user_entity.as_ref()),
        }))))
    }

    /// solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
    fn fail_if_wrong_input(&self) -> AppResult<()> {
        if self.update_user_dto.get_auth_user_id() < 1 {
            return Err(
                UsersException::unauthorized_custom(tr(UsersMessageEnum::MISSING_OR_INVALID_API_TOKEN, &[])).into()
            );
        }
        let user_uuid = self.update_user_dto.get_user_uuid();
        if user_uuid != self.update_user_dto.get_auth_user_uuid() {
            return Err(UsersException::not_found_custom(tr(UsersMessageEnum::USER_X_NOT_FOUND, &[&user_uuid])).into());
        }
        Ok(())
    }

    fn fail_if_wrong_fields(&self) -> AppResult<()> {
        let errors = UserFieldsValidator::get_instance()
            .get_errors(self.update_user_dto.get_user_name(), self.update_user_dto.get_user_email());
        if errors.is_empty() {
            return Ok(());
        }

        Err(FieldsException::bad_request_custom(
            self.get_error_stack_message(&tr(SharedMessageEnum::FIELDS_VALIDATION_ERRORS, &[]), &errors),
        )
        .into())
    }

    fn fail_if_user_name_or_email_exists_in_other_user(&self) -> AppResult<()> {
        if self.users_reader_sqlite_repository.does_user_name_exist_in_other_user(&self.user_entity)? {
            return Err(UsersException::conflict_custom(tr(
                UsersMessageEnum::USER_NAME_X_ALREADY_EXISTS,
                &[&self.user_entity.get_user_name()],
            ))
            .into());
        }

        if self.users_reader_sqlite_repository.does_user_email_exist_in_other_user(&self.user_entity)? {
            return Err(UsersException::conflict_custom(tr(
                UsersMessageEnum::EMAIL_X_ALREADY_EXISTS,
                &[&self.user_entity.get_user_email()],
            ))
            .into());
        }
        Ok(())
    }
}
