use serde_json::Value;

use crate::modules::shared::application::primitives::Primitives;

#[derive(Debug, Clone, Default)]
pub struct ListUsersResultDto {
    /// lista de UserEntity::to_primitives
    users: Vec<Value>,
}

impl ListUsersResultDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        let users = primitives.get("users").and_then(|users| users.as_array()).cloned().unwrap_or_default();
        Self { users }
    }

    pub fn get_users(&self) -> &[Value] {
        &self.users
    }

    pub fn to_array(&self) -> Value {
        Value::Array(self.users.clone())
    }
}
