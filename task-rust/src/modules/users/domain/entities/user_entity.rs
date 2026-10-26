use serde_json::{Value, json};

use crate::modules::shared::application::primitives::{Primitives, get_int, get_trimmed_string};
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;

pub const USER_UUID_PREFIX: &str = "user-";

#[derive(Debug, Clone, Default)]
pub struct UserEntity {
    id: i64,
    user_uuid: String,
    user_name: String,
    user_email: String,
    password_hash: String,
    api_token_hash: String,
    created_at: String,
    updated_at: String,
}

impl UserEntity {
    /// desde una fila de la tabla users (columnas en snake_case: contrato con la BD). Las lecturas no traen
    /// password_hash ni api_token_hash: solo los informan las escrituras.
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self {
            id: get_int(primitives, UsersKeyEnum::ID),
            user_uuid: get_trimmed_string(primitives, UsersKeyEnum::USER_UUID),
            user_name: get_trimmed_string(primitives, UsersKeyEnum::USER_NAME),
            user_email: get_trimmed_string(primitives, UsersKeyEnum::USER_EMAIL),
            password_hash: get_trimmed_string(primitives, UsersKeyEnum::PASSWORD_HASH),
            api_token_hash: get_trimmed_string(primitives, UsersKeyEnum::API_TOKEN_HASH),
            created_at: get_trimmed_string(primitives, UsersKeyEnum::CREATED_AT),
            updated_at: get_trimmed_string(primitives, UsersKeyEnum::UPDATED_AT),
        }
    }

    /// representacion publica (API): el id interno no sale, el identificador es user_uuid. Los hashes nunca. Las
    /// claves salen en este orden, como en task-php.
    pub fn to_primitives(&self) -> Value {
        json!({
            UsersKeyEnum::USER_UUID: self.user_uuid,
            UsersKeyEnum::USER_NAME: self.user_name,
            UsersKeyEnum::USER_EMAIL: self.user_email,
            UsersKeyEnum::CREATED_AT: self.created_at,
            UsersKeyEnum::UPDATED_AT: self.updated_at,
        })
    }

    /// el ?->toPrimitives() ?? [] de task-php: una lectura que no encuentra nada da el usuario vacio.
    pub fn to_primitives_or_empty(user_entity: Option<&UserEntity>) -> Value {
        user_entity.map_or_else(|| json!({}), UserEntity::to_primitives)
    }

    pub fn get_id(&self) -> i64 {
        self.id
    }

    pub fn get_user_uuid(&self) -> &str {
        &self.user_uuid
    }

    pub fn get_user_name(&self) -> &str {
        &self.user_name
    }

    pub fn get_user_email(&self) -> &str {
        &self.user_email
    }

    pub fn get_password_hash(&self) -> &str {
        &self.password_hash
    }

    pub fn get_api_token_hash(&self) -> &str {
        &self.api_token_hash
    }

    pub fn get_created_at(&self) -> &str {
        &self.created_at
    }

    pub fn get_updated_at(&self) -> &str {
        &self.updated_at
    }
}
