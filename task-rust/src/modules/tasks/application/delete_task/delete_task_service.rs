use serde_json::json;

use crate::core::config::tr;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::tasks::application::delete_task::delete_task_dto::DeleteTaskDto;
use crate::modules::tasks::application::delete_task::delete_task_result_dto::DeleteTaskResultDto;
use crate::modules::tasks::domain::entities::task_entity::TaskEntity;
use crate::modules::tasks::domain::enums::tasks_message_enum::TasksMessageEnum;
use crate::modules::tasks::domain::exceptions::tasks_exception::TasksException;
use crate::modules::tasks::infrastructure::repositories::tasks_reader_sqlite_repository::TasksReaderSqliteRepository;
use crate::modules::tasks::infrastructure::repositories::tasks_writer_sqlite_repository::TasksWriterSqliteRepository;

pub struct DeleteTaskService {
    tasks_reader_sqlite_repository: TasksReaderSqliteRepository,
    tasks_writer_sqlite_repository: TasksWriterSqliteRepository,

    delete_task_dto: DeleteTaskDto,
}

impl DeleteTaskService {
    pub fn get_instance() -> Self {
        Self {
            tasks_reader_sqlite_repository: TasksReaderSqliteRepository::get_instance(),
            tasks_writer_sqlite_repository: TasksWriterSqliteRepository::get_instance(),
            delete_task_dto: DeleteTaskDto::default(),
        }
    }

    /// error: TasksException (401, 404) o uno inesperado.
    pub fn invoke(&mut self, delete_task_dto: DeleteTaskDto) -> AppResult<DeleteTaskResultDto> {
        self.delete_task_dto = delete_task_dto;
        self.fail_if_wrong_input()?;

        let task_entity = self.try_to_get_own_task_or_fail()?;

        let affected = self.tasks_writer_sqlite_repository.delete_task_by_id(&task_entity)?;
        Ok(DeleteTaskResultDto::from_primitives(&get_primitives(json!({
            "taskUuid": task_entity.get_task_uuid(),
            "affected": affected,
        }))))
    }

    fn fail_if_wrong_input(&self) -> AppResult<()> {
        if self.delete_task_dto.get_auth_user_id() < 1 {
            return Err(
                TasksException::unauthorized_custom(tr(TasksMessageEnum::MISSING_OR_INVALID_API_TOKEN, &[])).into()
            );
        }
        Ok(())
    }

    /// una task de otro usuario responde igual que si no existiera (no se revela que existe).
    fn try_to_get_own_task_or_fail(&self) -> AppResult<TaskEntity> {
        let task_uuid = self.delete_task_dto.get_task_uuid();
        match self.tasks_reader_sqlite_repository.get_task_by_task_uuid(task_uuid)? {
            Some(task_entity) if task_entity.get_user_id() == self.delete_task_dto.get_auth_user_id() => {
                Ok(task_entity)
            }
            _ => Err(TasksException::not_found_custom(tr(TasksMessageEnum::TASK_X_NOT_FOUND, &[&task_uuid])).into()),
        }
    }
}
