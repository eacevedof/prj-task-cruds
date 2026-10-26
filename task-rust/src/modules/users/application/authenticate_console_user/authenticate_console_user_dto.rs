use crate::modules::shared::application::abstract_input_dto::AbstractInputDto;
use crate::modules::shared::application::primitives::{Primitives, get_trimmed_string};

#[derive(Debug, Clone, Default)]
pub struct AuthenticateConsoleUserDto {
    abstract_input_dto: AbstractInputDto,
    user_uuid: String,
}

extends!(AuthenticateConsoleUserDto, abstract_input_dto, AbstractInputDto);

impl AuthenticateConsoleUserDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self {
            abstract_input_dto: AbstractInputDto::from_primitives(primitives),
            user_uuid: get_trimmed_string(primitives, "userUuid"),
        }
    }

    pub fn get_user_uuid(&self) -> &str {
        &self.user_uuid
    }
}
