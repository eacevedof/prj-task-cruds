use crate::modules::shared::application::abstract_input_dto::AbstractInputDto;
use crate::modules::shared::application::primitives::{Primitives, get_int, get_int_or_default, get_trimmed_string};
use crate::modules::tasks::domain::services::tasks_search_validator::{PAGE_MIN, PAGE_SIZE_DEFAULT};

#[derive(Debug, Clone, Default)]
pub struct SearchTasksDto {
    abstract_input_dto: AbstractInputDto,
    auth_user_id: i64,
    auth_user_uuid: String,
    user_uuid: String,
    task_text: String,
    task_status: String,
    task_type: String,
    target_date_from: String,
    target_date_to: String,
    page: i64,
    page_size: i64,
}

extends!(SearchTasksDto, abstract_input_dto, AbstractInputDto);

impl SearchTasksDto {
    /// userUuid vacio = las del usuario autenticado. Sin page/pageSize: primera pagina con el tamaño por defecto.
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self {
            abstract_input_dto: AbstractInputDto::from_primitives(primitives),
            auth_user_id: get_int(primitives, "authUserId"),
            auth_user_uuid: get_trimmed_string(primitives, "authUserUuid"),
            user_uuid: get_trimmed_string(primitives, "userUuid"),
            task_text: get_trimmed_string(primitives, "taskText"),
            task_status: get_trimmed_string(primitives, "taskStatus"),
            task_type: get_trimmed_string(primitives, "taskType"),
            target_date_from: get_trimmed_string(primitives, "targetDateFrom"),
            target_date_to: get_trimmed_string(primitives, "targetDateTo"),
            page: get_int_or_default(primitives, "page", PAGE_MIN),
            page_size: get_int_or_default(primitives, "pageSize", PAGE_SIZE_DEFAULT),
        }
    }

    pub fn get_auth_user_id(&self) -> i64 {
        self.auth_user_id
    }

    pub fn get_auth_user_uuid(&self) -> &str {
        &self.auth_user_uuid
    }

    pub fn get_user_uuid(&self) -> &str {
        &self.user_uuid
    }

    pub fn get_task_text(&self) -> &str {
        &self.task_text
    }

    pub fn get_task_status(&self) -> &str {
        &self.task_status
    }

    pub fn get_task_type(&self) -> &str {
        &self.task_type
    }

    pub fn get_target_date_from(&self) -> &str {
        &self.target_date_from
    }

    pub fn get_target_date_to(&self) -> &str {
        &self.target_date_to
    }

    pub fn get_page(&self) -> i64 {
        self.page
    }

    pub fn get_page_size(&self) -> i64 {
        self.page_size
    }
}
