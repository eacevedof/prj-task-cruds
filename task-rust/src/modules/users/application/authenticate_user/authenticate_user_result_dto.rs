use serde_json::json;

use crate::modules::shared::application::primitives::{Primitives, get_int, get_primitives, get_trimmed_string};

/// user_id es el que usan todas las queries (mas ligero que el uuid); user_uuid solo para comparar con el user_uuid
/// que llega de fuera (argumento GraphQL, opcion de consola).
#[derive(Debug, Clone, Default)]
pub struct AuthenticateUserResultDto {
    user_id: i64,
    user_uuid: String,
}

impl AuthenticateUserResultDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self { user_id: get_int(primitives, "userId"), user_uuid: get_trimmed_string(primitives, "userUuid") }
    }

    pub fn get_user_id(&self) -> i64 {
        self.user_id
    }

    pub fn get_user_uuid(&self) -> &str {
        &self.user_uuid
    }

    pub fn to_auth_primitives(&self) -> Primitives {
        get_primitives(json!({
            "authUserId": self.user_id,
            "authUserUuid": self.user_uuid,
        }))
    }
}
