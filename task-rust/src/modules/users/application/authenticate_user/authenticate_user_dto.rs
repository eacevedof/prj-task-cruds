use crate::modules::shared::application::abstract_input_dto::AbstractInputDto;
use crate::modules::shared::application::primitives::{Primitives, get_trimmed_string};

#[derive(Debug, Clone, Default)]
pub struct AuthenticateUserDto {
    abstract_input_dto: AbstractInputDto,
    api_token: String,
}

extends!(AuthenticateUserDto, abstract_input_dto, AbstractInputDto);

impl AuthenticateUserDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self {
            abstract_input_dto: AbstractInputDto::from_primitives(primitives),
            api_token: get_trimmed_string(primitives, "apiToken"),
        }
    }

    pub fn get_api_token(&self) -> &str {
        &self.api_token
    }
}
