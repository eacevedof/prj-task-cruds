use serde_json::json;

use crate::core::config::tr;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::domain::enums::shared_message_enum::SharedMessageEnum;
use crate::modules::shared::infrastructure::components::date::date_timer::DateTimer;
use crate::modules::shared::infrastructure::components::password::password_hasher::PasswordHasher;
use crate::modules::shared::infrastructure::components::token::token_generator::TokenGenerator;
use crate::modules::shared::infrastructure::components::uuid::uuid_generator::UuidGenerator;
use crate::modules::shared::infrastructure::exceptions::fields_exception::FieldsException;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::traits::error_stack_trait::ErrorStackTrait;
use crate::modules::users::application::create_user::create_user_dto::CreateUserDto;
use crate::modules::users::application::create_user::create_user_result_dto::CreateUserResultDto;
use crate::modules::users::domain::entities::user_entity::{USER_UUID_PREFIX, UserEntity};
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::enums::users_message_enum::UsersMessageEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;
use crate::modules::users::domain::services::user_fields_validator::UserFieldsValidator;
use crate::modules::users::infrastructure::repositories::users_reader_sqlite_repository::UsersReaderSqliteRepository;
use crate::modules::users::infrastructure::repositories::users_writer_sqlite_repository::UsersWriterSqliteRepository;

/// alta abierta (sin token): devuelve el primer api_token en claro; despues, el login o regenerar dan uno nuevo.
pub struct CreateUserService {
    date_timer: DateTimer,
    password_hasher: PasswordHasher,
    token_generator: TokenGenerator,
    users_reader_sqlite_repository: UsersReaderSqliteRepository,
    users_writer_sqlite_repository: UsersWriterSqliteRepository,

    create_user_dto: CreateUserDto,
}

impl ErrorStackTrait for CreateUserService {}

impl CreateUserService {
    pub fn get_instance() -> Self {
        Self {
            date_timer: DateTimer::get_instance(),
            password_hasher: PasswordHasher::get_instance(),
            token_generator: TokenGenerator::get_instance(),
            users_reader_sqlite_repository: UsersReaderSqliteRepository::get_instance(),
            users_writer_sqlite_repository: UsersWriterSqliteRepository::get_instance(),
            create_user_dto: CreateUserDto::default(),
        }
    }

    /// error: FieldsException (400), UsersException (409) o uno inesperado.
    pub fn invoke(&mut self, create_user_dto: CreateUserDto) -> AppResult<CreateUserResultDto> {
        self.create_user_dto = create_user_dto;

        self.fail_if_wrong_input()?;
        self.fail_if_user_name_or_email_exists()?;

        let password_hash = self.password_hasher.get_password_hash(self.create_user_dto.get_password())?;
        let api_token = self.token_generator.get_new_token();
        let user_id =
            self.users_writer_sqlite_repository.insert_user(&UserEntity::from_primitives(&get_primitives(json!({
                UsersKeyEnum::USER_UUID: UuidGenerator::get_instance().get_unique_id_with_prefix(USER_UUID_PREFIX),
                UsersKeyEnum::USER_NAME: self.create_user_dto.get_user_name(),
                UsersKeyEnum::USER_EMAIL: self.create_user_dto.get_user_email(),
                UsersKeyEnum::PASSWORD_HASH: password_hash,
                UsersKeyEnum::API_TOKEN_HASH: self.token_generator.get_token_hash(&api_token),
                UsersKeyEnum::CREATED_AT: self.date_timer.get_now_dt_into_target_tz(),
            }))))?;

        let user_entity = self.users_reader_sqlite_repository.get_user_by_id(user_id)?;
        Ok(CreateUserResultDto::from_primitives(&get_primitives(json!({
            "user": UserEntity::to_primitives_or_empty(user_entity.as_ref()),
            "apiToken": api_token,
        }))))
    }

    fn fail_if_wrong_input(&self) -> AppResult<()> {
        let user_fields_validator = UserFieldsValidator::get_instance();
        let mut errors = user_fields_validator
            .get_errors(self.create_user_dto.get_user_name(), self.create_user_dto.get_user_email());
        errors.extend(user_fields_validator.get_password_errors(self.create_user_dto.get_password()));
        if errors.is_empty() {
            return Ok(());
        }

        Err(FieldsException::bad_request_custom(
            self.get_error_stack_message(&tr(SharedMessageEnum::FIELDS_VALIDATION_ERRORS, &[]), &errors),
        )
        .into())
    }

    fn fail_if_user_name_or_email_exists(&self) -> AppResult<()> {
        let user_name = self.create_user_dto.get_user_name();
        if self.users_reader_sqlite_repository.does_user_name_exist(user_name)? {
            return Err(UsersException::conflict_custom(tr(
                UsersMessageEnum::USER_NAME_X_ALREADY_EXISTS,
                &[&user_name],
            ))
            .into());
        }

        let user_email = self.create_user_dto.get_user_email();
        if self.users_reader_sqlite_repository.does_user_email_exist(user_email)? {
            return Err(
                UsersException::conflict_custom(tr(UsersMessageEnum::EMAIL_X_ALREADY_EXISTS, &[&user_email])).into()
            );
        }
        Ok(())
    }
}
