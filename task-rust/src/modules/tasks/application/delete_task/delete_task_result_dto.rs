use serde_json::{Value, json};

use crate::modules::shared::application::primitives::{Primitives, get_int, get_trimmed_string};
use crate::modules::tasks::domain::enums::tasks_key_enum::TasksKeyEnum;

#[derive(Debug, Clone, Default)]
pub struct DeleteTaskResultDto {
    task_uuid: String,
    affected: i64,
}

impl DeleteTaskResultDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self { task_uuid: get_trimmed_string(primitives, "taskUuid"), affected: get_int(primitives, "affected") }
    }

    pub fn get_task_uuid(&self) -> &str {
        &self.task_uuid
    }

    pub fn get_affected(&self) -> i64 {
        self.affected
    }

    /// tipo DeleteTaskResult de tasks.graphql.
    pub fn to_array(&self) -> Value {
        json!({
            TasksKeyEnum::TASK_UUID: self.task_uuid,
            TasksKeyEnum::AFFECTED: self.affected,
        })
    }
}
