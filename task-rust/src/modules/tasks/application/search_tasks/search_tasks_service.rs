use serde_json::json;

use crate::core::config::tr;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::domain::enums::shared_message_enum::SharedMessageEnum;
use crate::modules::shared::infrastructure::exceptions::fields_exception::FieldsException;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;
use crate::modules::shared::infrastructure::traits::error_stack_trait::ErrorStackTrait;
use crate::modules::tasks::application::search_tasks::search_tasks_dto::SearchTasksDto;
use crate::modules::tasks::application::search_tasks::search_tasks_result_dto::SearchTasksResultDto;
use crate::modules::tasks::domain::enums::tasks_message_enum::TasksMessageEnum;
use crate::modules::tasks::domain::exceptions::tasks_exception::TasksException;
use crate::modules::tasks::domain::services::tasks_search_validator::TasksSearchValidator;
use crate::modules::tasks::infrastructure::repositories::tasks_reader_sqlite_repository::TasksReaderSqliteRepository;

/// busqueda paginada de las tasks del usuario autenticado (mas reciente primero): filtros por texto, estado, tipo y
/// rango de target_date.
pub struct SearchTasksService {
    tasks_search_validator: TasksSearchValidator,
    tasks_reader_sqlite_repository: TasksReaderSqliteRepository,

    search_tasks_dto: SearchTasksDto,
}

impl ErrorStackTrait for SearchTasksService {}

impl SearchTasksService {
    pub fn get_instance() -> Self {
        Self {
            tasks_search_validator: TasksSearchValidator::get_instance(),
            tasks_reader_sqlite_repository: TasksReaderSqliteRepository::get_instance(),
            search_tasks_dto: SearchTasksDto::default(),
        }
    }

    /// error: FieldsException (400), TasksException (401, 404) o uno inesperado.
    pub fn invoke(&mut self, search_tasks_dto: SearchTasksDto) -> AppResult<SearchTasksResultDto> {
        self.search_tasks_dto = search_tasks_dto;
        self.fail_if_wrong_input()?;
        self.fail_if_wrong_search()?;

        let user_id = self.search_tasks_dto.get_auth_user_id();

        let total_items = self.tasks_reader_sqlite_repository.get_tasks_count_by_user_id_and_filters(
            user_id,
            self.search_tasks_dto.get_task_text(),
            self.search_tasks_dto.get_task_status(),
            self.search_tasks_dto.get_task_type(),
            self.search_tasks_dto.get_target_date_from(),
            self.search_tasks_dto.get_target_date_to(),
        )?;
        let page = self.search_tasks_dto.get_page();
        let page_size = self.search_tasks_dto.get_page_size();
        let task_entities = self.tasks_reader_sqlite_repository.get_tasks_by_user_id_and_filters(
            user_id,
            self.search_tasks_dto.get_task_text(),
            self.search_tasks_dto.get_task_status(),
            self.search_tasks_dto.get_task_type(),
            self.search_tasks_dto.get_target_date_from(),
            self.search_tasks_dto.get_target_date_to(),
            page_size,
            (page - 1) * page_size,
        )?;

        let tasks: Vec<_> = task_entities.iter().map(|task_entity| task_entity.to_primitives()).collect();
        Ok(SearchTasksResultDto::from_primitives(&get_primitives(json!({
            "tasks": tasks,
            "page": page,
            "pageSize": page_size,
            "totalItems": total_items,
            // ceil(totalItems / pageSize) en enteros
            "totalPages": (total_items + page_size - 1) / page_size,
        }))))
    }

    /// sin user_uuid se busca en las del autenticado; con uno ajeno responde igual que si no existiera.
    fn fail_if_wrong_input(&self) -> AppResult<()> {
        if self.search_tasks_dto.get_auth_user_id() < 1 {
            return Err(
                TasksException::unauthorized_custom(tr(TasksMessageEnum::MISSING_OR_INVALID_API_TOKEN, &[])).into()
            );
        }
        let mut user_uuid = self.search_tasks_dto.get_user_uuid();
        if user_uuid.is_empty() {
            user_uuid = self.search_tasks_dto.get_auth_user_uuid();
        }
        if user_uuid != self.search_tasks_dto.get_auth_user_uuid() {
            return Err(TasksException::not_found_custom(tr(TasksMessageEnum::USER_X_NOT_FOUND, &[&user_uuid])).into());
        }
        Ok(())
    }

    fn fail_if_wrong_search(&self) -> AppResult<()> {
        let errors = self.tasks_search_validator.get_errors(
            self.search_tasks_dto.get_task_status(),
            self.search_tasks_dto.get_task_type(),
            self.search_tasks_dto.get_target_date_from(),
            self.search_tasks_dto.get_target_date_to(),
            self.search_tasks_dto.get_page(),
            self.search_tasks_dto.get_page_size(),
        );
        if errors.is_empty() {
            return Ok(());
        }

        Err(FieldsException::bad_request_custom(
            self.get_error_stack_message(&tr(SharedMessageEnum::FIELDS_VALIDATION_ERRORS, &[]), &errors),
        )
        .into())
    }
}
