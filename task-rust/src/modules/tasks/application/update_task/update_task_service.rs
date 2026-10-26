use serde_json::json;

use crate::core::config::tr;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::domain::enums::shared_message_enum::SharedMessageEnum;
use crate::modules::shared::infrastructure::components::date::date_timer::DateTimer;
use crate::modules::shared::infrastructure::exceptions::fields_exception::FieldsException;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::traits::error_stack_trait::ErrorStackTrait;
use crate::modules::tasks::application::update_task::update_task_dto::UpdateTaskDto;
use crate::modules::tasks::application::update_task::update_task_result_dto::UpdateTaskResultDto;
use crate::modules::tasks::domain::entities::task_entity::TaskEntity;
use crate::modules::tasks::domain::enums::task_status_enum::TaskStatusEnum;
use crate::modules::tasks::domain::enums::task_type_enum::TaskTypeEnum;
use crate::modules::tasks::domain::enums::tasks_key_enum::TasksKeyEnum;
use crate::modules::tasks::domain::enums::tasks_message_enum::TasksMessageEnum;
use crate::modules::tasks::domain::exceptions::tasks_exception::TasksException;
use crate::modules::tasks::domain::services::task_fields_validator::TaskFieldsValidator;
use crate::modules::tasks::infrastructure::repositories::tasks_reader_sqlite_repository::TasksReaderSqliteRepository;
use crate::modules::tasks::infrastructure::repositories::tasks_writer_sqlite_repository::TasksWriterSqliteRepository;

/// PUT: reemplazo completo (mismas reglas y defaults que el alta). El propietario no cambia.
pub struct UpdateTaskService {
    date_timer: DateTimer,
    tasks_reader_sqlite_repository: TasksReaderSqliteRepository,
    tasks_writer_sqlite_repository: TasksWriterSqliteRepository,

    update_task_dto: UpdateTaskDto,
    task_entity: TaskEntity,
}

impl ErrorStackTrait for UpdateTaskService {}

impl UpdateTaskService {
    pub fn get_instance() -> Self {
        Self {
            date_timer: DateTimer::get_instance(),
            tasks_reader_sqlite_repository: TasksReaderSqliteRepository::get_instance(),
            tasks_writer_sqlite_repository: TasksWriterSqliteRepository::get_instance(),
            update_task_dto: UpdateTaskDto::default(),
            task_entity: TaskEntity::default(),
        }
    }

    /// error: FieldsException (400), TasksException (401, 404) o uno inesperado.
    pub fn invoke(&mut self, update_task_dto: UpdateTaskDto) -> AppResult<UpdateTaskResultDto> {
        self.update_task_dto = update_task_dto;
        self.fail_if_wrong_input()?;

        let current_task_entity = self.try_to_get_own_task_or_fail()?;
        // reemplazo completo sobre la task actual: se conservan id, propietario y created_at
        self.task_entity = TaskEntity::from_primitives(&get_primitives(json!({
            TasksKeyEnum::ID: current_task_entity.get_id(),
            TasksKeyEnum::USER_ID: current_task_entity.get_user_id(),
            TasksKeyEnum::USER_UUID: current_task_entity.get_user_uuid(),
            TasksKeyEnum::TASK_UUID: current_task_entity.get_task_uuid(),
            TasksKeyEnum::TASK_TITLE: self.update_task_dto.get_task_title(),
            TasksKeyEnum::TASK_STATUS: get_value_or_default(self.update_task_dto.get_task_status(), TaskStatusEnum::DEFAULT),
            TasksKeyEnum::TASK_TYPE: get_value_or_default(self.update_task_dto.get_task_type(), TaskTypeEnum::DEFAULT),
            TasksKeyEnum::TARGET_DATE: self.update_task_dto.get_target_date(),
            TasksKeyEnum::TASK_DESCRIPTION: self.update_task_dto.get_task_description(),
            TasksKeyEnum::CREATED_AT: current_task_entity.get_created_at(),
            TasksKeyEnum::UPDATED_AT: self.date_timer.get_now_dt_into_target_tz(),
        })));

        self.fail_if_wrong_fields()?;

        self.tasks_writer_sqlite_repository.update_task_by_id(&self.task_entity)?;

        let task_entity = self.tasks_reader_sqlite_repository.get_task_by_id(self.task_entity.get_id())?;
        Ok(UpdateTaskResultDto::from_primitives(&get_primitives(json!({
            "task": TaskEntity::to_primitives_or_empty(task_entity.as_ref()),
        }))))
    }

    fn fail_if_wrong_input(&self) -> AppResult<()> {
        if self.update_task_dto.get_auth_user_id() < 1 {
            return Err(
                TasksException::unauthorized_custom(tr(TasksMessageEnum::MISSING_OR_INVALID_API_TOKEN, &[])).into()
            );
        }
        Ok(())
    }

    /// una task de otro usuario responde igual que si no existiera (no se revela que existe).
    fn try_to_get_own_task_or_fail(&self) -> AppResult<TaskEntity> {
        let task_uuid = self.update_task_dto.get_task_uuid();
        match self.tasks_reader_sqlite_repository.get_task_by_task_uuid(task_uuid)? {
            Some(task_entity) if task_entity.get_user_id() == self.update_task_dto.get_auth_user_id() => {
                Ok(task_entity)
            }
            _ => Err(TasksException::not_found_custom(tr(TasksMessageEnum::TASK_X_NOT_FOUND, &[&task_uuid])).into()),
        }
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
