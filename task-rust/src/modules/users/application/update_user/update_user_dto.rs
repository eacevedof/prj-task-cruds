use crate::modules::shared::application::abstract_input_dto::AbstractInputDto;
use crate::modules::shared::application::primitives::{Primitives, get_int, get_trimmed_string};

#[derive(Debug, Clone, Default)]
pub struct UpdateUserDto {
    abstract_input_dto: AbstractInputDto,
    auth_user_id: i64,
    auth_user_uuid: String,
    user_uuid: String,
    user_name: String,
    user_email: String,
}

extends!(UpdateUserDto, abstract_input_dto, AbstractInputDto);

impl UpdateUserDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self {
            abstract_input_dto: AbstractInputDto::from_primitives(primitives),
            auth_user_id: get_int(primitives, "authUserId"),
            auth_user_uuid: get_trimmed_string(primitives, "authUserUuid"),
            user_uuid: get_trimmed_string(primitives, "userUuid"),
            user_name: get_trimmed_string(primitives, "userName"),
            user_email: get_trimmed_string(primitives, "userEmail"),
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

    pub fn get_user_name(&self) -> &str {
        &self.user_name
    }

    pub fn get_user_email(&self) -> &str {
        &self.user_email
    }
}
