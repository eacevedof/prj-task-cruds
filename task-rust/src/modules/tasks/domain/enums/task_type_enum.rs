/// valores de tasks.task_type (los mismos que el CHECK de la migracion create-tasks-table).
pub struct TaskTypeEnum;

impl TaskTypeEnum {
    pub const PERSONAL: &'static str = "personal";
    pub const WORK: &'static str = "work";
    pub const OTHER: &'static str = "other";
    pub const DEFAULT: &'static str = "personal";
    pub const VALUES: [&'static str; 3] = ["personal", "work", "other"];
}
