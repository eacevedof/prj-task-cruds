use serde_json::{Value, json};

use crate::modules::shared::application::primitives::Primitives;

#[derive(Debug, Clone, Default)]
pub struct GetUserResultDto {
    /// el usuario (UserEntity::to_primitives)
    user: Value,
}

impl GetUserResultDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self { user: primitives.get("user").cloned().unwrap_or_else(|| json!({})) }
    }

    pub fn get_user(&self) -> &Value {
        &self.user
    }

    pub fn to_array(&self) -> Value {
        self.user.clone()
    }
}
