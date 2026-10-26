use serde_json::{Value, json};

use crate::modules::shared::application::primitives::{Primitives, get_trimmed_string};
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;

#[derive(Debug, Clone, Default)]
pub struct LogInUserResultDto {
    /// el usuario (UserEntity::to_primitives) + su api_token nuevo en claro, solo aqui
    user: Value,
    api_token: String,
}

impl LogInUserResultDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self {
            user: primitives.get("user").cloned().unwrap_or_else(|| json!({})),
            api_token: get_trimmed_string(primitives, "apiToken"),
        }
    }

    pub fn get_user(&self) -> &Value {
        &self.user
    }

    pub fn get_api_token(&self) -> &str {
        &self.api_token
    }

    /// el usuario con su api_token detras.
    pub fn to_array(&self) -> Value {
        let mut user_with_api_token = self.user.clone();
        if let Value::Object(user) = &mut user_with_api_token {
            user.insert(UsersKeyEnum::API_TOKEN.to_string(), Value::from(self.api_token.clone()));
        }
        user_with_api_token
    }
}
