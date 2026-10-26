use serde_json::{Value, json};

use crate::modules::shared::application::primitives::{Primitives, get_int, get_trimmed_string};
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;

#[derive(Debug, Clone, Default)]
pub struct DeleteUserResultDto {
    user_uuid: String,
    affected: i64,
}

impl DeleteUserResultDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self { user_uuid: get_trimmed_string(primitives, "userUuid"), affected: get_int(primitives, "affected") }
    }

    pub fn get_user_uuid(&self) -> &str {
        &self.user_uuid
    }

    pub fn get_affected(&self) -> i64 {
        self.affected
    }

    /// tipo DeleteUserResult de schema.graphql.
    pub fn to_array(&self) -> Value {
        json!({
            UsersKeyEnum::USER_UUID: self.user_uuid,
            UsersKeyEnum::AFFECTED: self.affected,
        })
    }
}
