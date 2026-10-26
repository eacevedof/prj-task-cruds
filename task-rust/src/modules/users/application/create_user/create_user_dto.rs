use crate::modules::shared::application::abstract_input_dto::AbstractInputDto;
use crate::modules::shared::application::primitives::{Primitives, get_trimmed_string};

#[derive(Debug, Clone, Default)]
pub struct CreateUserDto {
    abstract_input_dto: AbstractInputDto,
    user_name: String,
    user_email: String,
    password: String,
}

extends!(CreateUserDto, abstract_input_dto, AbstractInputDto);

impl CreateUserDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self {
            abstract_input_dto: AbstractInputDto::from_primitives(primitives),
            user_name: get_trimmed_string(primitives, "userName"),
            user_email: get_trimmed_string(primitives, "userEmail"),
            password: get_trimmed_string(primitives, "password"),
        }
    }

    pub fn get_user_name(&self) -> &str {
        &self.user_name
    }

    pub fn get_user_email(&self) -> &str {
        &self.user_email
    }

    pub fn get_password(&self) -> &str {
        &self.password
    }
}
