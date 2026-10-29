use serde_json::{Value, json};

use crate::modules::shared::application::primitives::{Primitives, get_trimmed_string};
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;

#[derive(Debug, Clone, Default)]
pub struct RegenerateUserApiTokenResultDto {
    user_uuid: String,
    api_token: String,
}

impl RegenerateUserApiTokenResultDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self {
            user_uuid: get_trimmed_string(primitives, "userUuid"),
            api_token: get_trimmed_string(primitives, "apiToken"),
        }
    }

    pub fn get_user_uuid(&self) -> &str {
        &self.user_uuid
    }

    pub fn get_api_token(&self) -> &str {
        &self.api_token
    }

    /// tipo ApiTokenResult de users.graphql.
    pub fn to_array(&self) -> Value {
        json!({
            UsersKeyEnum::USER_UUID: self.user_uuid,
            UsersKeyEnum::API_TOKEN: self.api_token,
        })
    }
}
