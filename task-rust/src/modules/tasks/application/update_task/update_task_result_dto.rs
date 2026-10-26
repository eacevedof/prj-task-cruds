use serde_json::{Value, json};

use crate::modules::shared::application::primitives::Primitives;

#[derive(Debug, Clone, Default)]
pub struct UpdateTaskResultDto {
    /// la task modificada (TaskEntity::to_primitives)
    task: Value,
}

impl UpdateTaskResultDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self { task: primitives.get("task").cloned().unwrap_or_else(|| json!({})) }
    }

    pub fn get_task(&self) -> &Value {
        &self.task
    }

    pub fn to_array(&self) -> Value {
        self.task.clone()
    }
}
