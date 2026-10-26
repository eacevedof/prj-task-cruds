use std::error::Error;
use std::fmt;

use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::infrastructure::exceptions::interface_exception::InterfaceException;

/// error de dominio de users con su codigo HTTP equivalente (401, 404, 409).
#[derive(Debug)]
pub struct UsersException {
    message: String,
    code: i64,
}

impl UsersException {
    pub fn unauthorized_custom(message: String) -> Self {
        Self { message, code: ExceptionEnum::CODE_UNAUTHORIZED }
    }

    pub fn not_found_custom(message: String) -> Self {
        Self { message, code: ExceptionEnum::CODE_NOT_FOUND }
    }

    pub fn conflict_custom(message: String) -> Self {
        Self { message, code: ExceptionEnum::CODE_CONFLICT }
    }
}

impl InterfaceException for UsersException {
    fn get_code(&self) -> i64 {
        self.code
    }
}

impl fmt::Display for UsersException {
    fn fmt(&self, formatter: &mut fmt::Formatter<'_>) -> fmt::Result {
        formatter.write_str(&self.message)
    }
}

impl Error for UsersException {}
