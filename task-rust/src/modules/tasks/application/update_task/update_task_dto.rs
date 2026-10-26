use crate::modules::shared::application::abstract_input_dto::AbstractInputDto;
use crate::modules::shared::application::primitives::{Primitives, get_int, get_trimmed_string};

#[derive(Debug, Clone, Default)]
pub struct UpdateTaskDto {
    abstract_input_dto: AbstractInputDto,
    auth_user_id: i64,
    auth_user_uuid: String,
    task_uuid: String,
    task_title: String,
    task_status: String,
    task_type: String,
    target_date: String,
    task_description: String,
}

extends!(UpdateTaskDto, abstract_input_dto, AbstractInputDto);

impl UpdateTaskDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self {
            abstract_input_dto: AbstractInputDto::from_primitives(primitives),
            auth_user_id: get_int(primitives, "authUserId"),
            auth_user_uuid: get_trimmed_string(primitives, "authUserUuid"),
            task_uuid: get_trimmed_string(primitives, "taskUuid"),
            task_title: get_trimmed_string(primitives, "taskTitle"),
            task_status: get_trimmed_string(primitives, "taskStatus"),
            task_type: get_trimmed_string(primitives, "taskType"),
            target_date: get_trimmed_string(primitives, "targetDate"),
            task_description: get_trimmed_string(primitives, "taskDescription"),
        }
    }

    pub fn get_auth_user_id(&self) -> i64 {
        self.auth_user_id
    }

    pub fn get_auth_user_uuid(&self) -> &str {
        &self.auth_user_uuid
    }

    pub fn get_task_uuid(&self) -> &str {
        &self.task_uuid
    }

    pub fn get_task_title(&self) -> &str {
        &self.task_title
    }

    pub fn get_task_status(&self) -> &str {
        &self.task_status
    }

    pub fn get_task_type(&self) -> &str {
        &self.task_type
    }

    pub fn get_target_date(&self) -> &str {
        &self.target_date
    }

    pub fn get_task_description(&self) -> &str {
        &self.task_description
    }
}
