use crate::modules::shared::application::abstract_input_dto::AbstractInputDto;
use crate::modules::shared::application::primitives::{Primitives, get_int, get_trimmed_string};

#[derive(Debug, Clone, Default)]
pub struct DeleteTaskDto {
    abstract_input_dto: AbstractInputDto,
    auth_user_id: i64,
    auth_user_uuid: String,
    task_uuid: String,
}

extends!(DeleteTaskDto, abstract_input_dto, AbstractInputDto);

impl DeleteTaskDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self {
            abstract_input_dto: AbstractInputDto::from_primitives(primitives),
            auth_user_id: get_int(primitives, "authUserId"),
            auth_user_uuid: get_trimmed_string(primitives, "authUserUuid"),
            task_uuid: get_trimmed_string(primitives, "taskUuid"),
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
}
