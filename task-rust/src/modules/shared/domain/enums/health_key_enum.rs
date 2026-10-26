/// respuesta de GET /health.
pub struct HealthKeyEnum;

impl HealthKeyEnum {
    pub const APP: &'static str = "app";
    pub const RUST: &'static str = "rust";
    pub const SQL_DRIVERS: &'static str = "sql_drivers";
    pub const APP_NAME: &'static str = "task-rust";
}
