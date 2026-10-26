use std::error::Error;
use std::fmt;

use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::infrastructure::exceptions::interface_exception::InterfaceException;

/// error de dominio de tasks con su codigo HTTP equivalente (401, 404).
#[derive(Debug)]
pub struct TasksException {
    message: String,
    code: i64,
}

impl TasksException {
    pub fn unauthorized_custom(message: String) -> Self {
        Self { message, code: ExceptionEnum::CODE_UNAUTHORIZED }
    }

    pub fn not_found_custom(message: String) -> Self {
        Self { message, code: ExceptionEnum::CODE_NOT_FOUND }
    }
}

impl InterfaceException for TasksException {
    fn get_code(&self) -> i64 {
        self.code
    }
}

impl fmt::Display for TasksException {
    fn fmt(&self, formatter: &mut fmt::Formatter<'_>) -> fmt::Result {
        formatter.write_str(&self.message)
    }
}

impl Error for TasksException {}
