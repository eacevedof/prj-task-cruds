use serde_json::{Value, json};

use crate::modules::shared::application::primitives::{Primitives, get_int, get_trimmed_string};
use crate::modules::tasks::domain::enums::tasks_key_enum::TasksKeyEnum;

pub const TASK_UUID_PREFIX: &str = "task-";

#[derive(Debug, Clone, Default)]
pub struct TaskEntity {
    id: i64,
    user_id: i64,
    user_uuid: String,
    task_uuid: String,
    task_title: String,
    task_status: String,
    task_type: String,
    target_date: String,
    task_description: String,
    created_at: String,
    updated_at: String,
}

impl TaskEntity {
    /// desde una fila de tasks JOIN users (user_uuid viene del usuario propietario; columnas en snake_case: contrato
    /// con la BD). target_date / task_description NULL en BD -> "" aqui (neutro de string).
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self {
            id: get_int(primitives, TasksKeyEnum::ID),
            user_id: get_int(primitives, TasksKeyEnum::USER_ID),
            user_uuid: get_trimmed_string(primitives, TasksKeyEnum::USER_UUID),
            task_uuid: get_trimmed_string(primitives, TasksKeyEnum::TASK_UUID),
            task_title: get_trimmed_string(primitives, TasksKeyEnum::TASK_TITLE),
            task_status: get_trimmed_string(primitives, TasksKeyEnum::TASK_STATUS),
            task_type: get_trimmed_string(primitives, TasksKeyEnum::TASK_TYPE),
            target_date: get_trimmed_string(primitives, TasksKeyEnum::TARGET_DATE),
            task_description: get_trimmed_string(primitives, TasksKeyEnum::TASK_DESCRIPTION),
            created_at: get_trimmed_string(primitives, TasksKeyEnum::CREATED_AT),
            updated_at: get_trimmed_string(primitives, TasksKeyEnum::UPDATED_AT),
        }
    }

    /// representacion publica (API): el identificador para operar es task_uuid; task_id es solo informativo (orden,
    /// referencia legible). El propietario va por user_uuid, nunca por su id interno. Los opcionales vacios salen
    /// como null (contrato de la API).
    pub fn to_primitives(&self) -> Value {
        json!({
            TasksKeyEnum::TASK_ID: self.id,
            TasksKeyEnum::TASK_UUID: self.task_uuid,
            TasksKeyEnum::USER_UUID: self.user_uuid,
            TasksKeyEnum::TASK_TITLE: self.task_title,
            TasksKeyEnum::TASK_STATUS: self.task_status,
            TasksKeyEnum::TASK_TYPE: self.task_type,
            TasksKeyEnum::TARGET_DATE: get_null_if_empty(&self.target_date),
            TasksKeyEnum::TASK_DESCRIPTION: get_null_if_empty(&self.task_description),
            TasksKeyEnum::CREATED_AT: self.created_at,
            TasksKeyEnum::UPDATED_AT: self.updated_at,
        })
    }

    /// el ?->toPrimitives() ?? [] de task-php: una lectura que no encuentra nada da la task vacia.
    pub fn to_primitives_or_empty(task_entity: Option<&TaskEntity>) -> Value {
        task_entity.map_or_else(|| json!({}), TaskEntity::to_primitives)
    }

    pub fn get_id(&self) -> i64 {
        self.id
    }

    pub fn get_user_id(&self) -> i64 {
        self.user_id
    }

    pub fn get_user_uuid(&self) -> &str {
        &self.user_uuid
    }

    pub fn get_task_uuid(&self) -> &str {
        &self.task_uuid
    }

    pub fn get_task_title(&self) -> &str {
        &self.task_title
    }

    pub fn get_task_status(&self) -> &str {
        &self.task_status
    }

    pub fn get_task_type(&self) -> &str {
        &self.task_type
    }

    pub fn get_target_date(&self) -> &str {
        &self.target_date
    }

    pub fn get_task_description(&self) -> &str {
        &self.task_description
    }

    pub fn get_created_at(&self) -> &str {
        &self.created_at
    }

    pub fn get_updated_at(&self) -> &str {
        &self.updated_at
    }
}

fn get_null_if_empty(value: &str) -> Value {
    if value.is_empty() { Value::Null } else { Value::from(value) }
}
