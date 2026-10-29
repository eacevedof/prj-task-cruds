use serde_json::{Value, json};

use crate::modules::shared::application::primitives::{Primitives, get_int};
use crate::modules::tasks::domain::enums::tasks_key_enum::TasksKeyEnum;

#[derive(Debug, Clone, Default)]
pub struct SearchTasksResultDto {
    /// la pagina pedida: lista de TaskEntity::to_primitives
    tasks: Vec<Value>,
    page: i64,
    page_size: i64,
    total_items: i64,
    total_pages: i64,
}

impl SearchTasksResultDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        Self {
            tasks: primitives.get("tasks").and_then(|tasks| tasks.as_array()).cloned().unwrap_or_default(),
            page: get_int(primitives, "page"),
            page_size: get_int(primitives, "pageSize"),
            total_items: get_int(primitives, "totalItems"),
            total_pages: get_int(primitives, "totalPages"),
        }
    }

    pub fn get_tasks(&self) -> &[Value] {
        &self.tasks
    }

    pub fn get_page(&self) -> i64 {
        self.page
    }

    pub fn get_page_size(&self) -> i64 {
        self.page_size
    }

    pub fn get_total_items(&self) -> i64 {
        self.total_items
    }

    pub fn get_total_pages(&self) -> i64 {
        self.total_pages
    }

    /// tipo TaskPage de tasks.graphql.
    pub fn to_array(&self) -> Value {
        json!({
            TasksKeyEnum::ITEMS: self.tasks,
            TasksKeyEnum::PAGE: self.page,
            TasksKeyEnum::PAGE_SIZE: self.page_size,
            TasksKeyEnum::TOTAL_ITEMS: self.total_items,
            TasksKeyEnum::TOTAL_PAGES: self.total_pages,
        })
    }
}
