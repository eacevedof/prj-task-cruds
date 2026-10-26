use std::error::Error;
use std::fmt;

use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::infrastructure::exceptions::interface_exception::InterfaceException;

/// validacion de campos: 400 con el detalle por campo ya metido en el mensaje (ErrorStackTrait::get_error_stack_message).
#[derive(Debug)]
pub struct FieldsException {
    message: String,
    code: i64,
}

impl FieldsException {
    pub fn bad_request_custom(message: String) -> Self {
        Self { message, code: ExceptionEnum::CODE_BAD_REQUEST }
    }
}

impl InterfaceException for FieldsException {
    fn get_code(&self) -> i64 {
        self.code
    }
}

impl fmt::Display for FieldsException {
    fn fmt(&self, formatter: &mut fmt::Formatter<'_>) -> fmt::Result {
        formatter.write_str(&self.message)
    }
}

impl Error for FieldsException {}
