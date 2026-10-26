/// valores de tasks.task_status (los mismos que el CHECK de la migracion create-tasks-table).
pub struct TaskStatusEnum;

impl TaskStatusEnum {
    pub const PENDING: &'static str = "pending";
    pub const IN_PROGRESS: &'static str = "in_progress";
    pub const DONE: &'static str = "done";
    pub const DEFAULT: &'static str = "pending";
    pub const VALUES: [&'static str; 3] = ["pending", "in_progress", "done"];
}
