use serde_json::{Value, json};

use crate::modules::shared::application::primitives::Primitives;

#[derive(Debug, Clone, Default)]
pub struct UpdateUserResultDto {
    /// el usuario modificado (UserEntity::to_primitives)
    user: Value,
}

impl UpdateUserResultDto {
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
