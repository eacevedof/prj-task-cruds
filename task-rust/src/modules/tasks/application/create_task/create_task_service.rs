use serde_json::json;

use crate::core::config::tr;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::domain::enums::shared_message_enum::SharedMessageEnum;
use crate::modules::shared::infrastructure::components::date::date_timer::DateTimer;
use crate::modules::shared::infrastructure::components::uuid::uuid_generator::UuidGenerator;
use crate::modules::shared::infrastructure::exceptions::fields_exception::FieldsException;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::traits::error_stack_trait::ErrorStackTrait;
use crate::modules::tasks::application::create_task::create_task_dto::CreateTaskDto;
use crate::modules::tasks::application::create_task::create_task_result_dto::CreateTaskResultDto;
use crate::modules::tasks::domain::entities::task_entity::{TASK_UUID_PREFIX, TaskEntity};
use crate::modules::tasks::domain::enums::task_status_enum::TaskStatusEnum;
use crate::modules::tasks::domain::enums::task_type_enum::TaskTypeEnum;
use crate::modules::tasks::domain::enums::tasks_key_enum::TasksKeyEnum;
use crate::modules::tasks::domain::enums::tasks_message_enum::TasksMessageEnum;
use crate::modules::tasks::domain::exceptions::tasks_exception::TasksException;
use crate::modules::tasks::domain::services::task_fields_validator::TaskFieldsValidator;
use crate::modules::tasks::infrastructure::repositories::tasks_reader_sqlite_repository::TasksReaderSqliteRepository;
use crate::modules::tasks::infrastructure::repositories::tasks_writer_sqlite_repository::TasksWriterSqliteRepository;

/// el dueño es siempre el usuario autenticado. task_status / task_type vacios -> su DEFAULT.
pub struct CreateTaskService {
    date_timer: DateTimer,
    tasks_reader_sqlite_repository: TasksReaderSqliteRepository,
    tasks_writer_sqlite_repository: TasksWriterSqliteRepository,

    create_task_dto: CreateTaskDto,
    task_entity: TaskEntity,
}

impl ErrorStackTrait for CreateTaskService {}

impl CreateTaskService {
    pub fn get_instance() -> Self {
        Self {
            date_timer: DateTimer::get_instance(),
            tasks_reader_sqlite_repository: TasksReaderSqliteRepository::get_instance(),
            tasks_writer_sqlite_repository: TasksWriterSqliteRepository::get_instance(),
            create_task_dto: CreateTaskDto::default(),
            task_entity: TaskEntity::default(),
        }
    }

    /// error: FieldsException (400), TasksException (401) o uno inesperado.
    pub fn invoke(&mut self, create_task_dto: CreateTaskDto) -> AppResult<CreateTaskResultDto> {
        self.create_task_dto = create_task_dto;
        self.fail_if_wrong_input()?;

        self.task_entity = TaskEntity::from_primitives(&get_primitives(json!({
            TasksKeyEnum::USER_ID: self.create_task_dto.get_auth_user_id(),
            TasksKeyEnum::USER_UUID: self.create_task_dto.get_auth_user_uuid(),
            TasksKeyEnum::TASK_UUID: UuidGenerator::get_instance().get_unique_id_with_prefix(TASK_UUID_PREFIX),
            TasksKeyEnum::TASK_TITLE: self.create_task_dto.get_task_title(),
            TasksKeyEnum::TASK_STATUS: get_value_or_default(self.create_task_dto.get_task_status(), TaskStatusEnum::DEFAULT),
            TasksKeyEnum::TASK_TYPE: get_value_or_default(self.create_task_dto.get_task_type(), TaskTypeEnum::DEFAULT),
            TasksKeyEnum::TARGET_DATE: self.create_task_dto.get_target_date(),
            TasksKeyEnum::TASK_DESCRIPTION: self.create_task_dto.get_task_description(),
            TasksKeyEnum::CREATED_AT: self.date_timer.get_now_dt_into_target_tz(),
        })));

        self.fail_if_wrong_fields()?;

        let task_id = self.tasks_writer_sqlite_repository.insert_task(&self.task_entity)?;

        let task_entity = self.tasks_reader_sqlite_repository.get_task_by_id(task_id)?;
        Ok(CreateTaskResultDto::from_primitives(&get_primitives(json!({
            "task": TaskEntity::to_primitives_or_empty(task_entity.as_ref()),
        }))))
    }

    /// por API nunca llega sin usuario (el controller corta con 401); protege otras entradas (consola, tests).
    fn fail_if_wrong_input(&self) -> AppResult<()> {
        if self.create_task_dto.get_auth_user_id() < 1 {
            return Err(
                TasksException::unauthorized_custom(tr(TasksMessageEnum::MISSING_OR_INVALID_API_TOKEN, &[])).into()
            );
        }
        Ok(())
    }

    fn fail_if_wrong_fields(&self) -> AppResult<()> {
        let errors = TaskFieldsValidator::get_instance().get_errors(&self.task_entity);
        if errors.is_empty() {
            return Ok(());
        }
        Err(FieldsException::bad_request_custom(
            self.get_error_stack_message(&tr(SharedMessageEnum::FIELDS_VALIDATION_ERRORS, &[]), &errors),
        )
        .into())
    }
}

/// el $valor ?: DEFAULT de task-php.
fn get_value_or_default<'a>(value: &'a str, default_value: &'a str) -> &'a str {
    if value.is_empty() { default_value } else { value }
}
