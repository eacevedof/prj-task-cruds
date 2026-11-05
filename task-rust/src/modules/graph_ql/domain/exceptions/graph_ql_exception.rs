use std::error::Error;
use std::fmt;

use serde_json::{Value, json};

use crate::modules::graph_ql::domain::enums::graph_ql_enum::GraphQlEnum;
use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::infrastructure::exceptions::interface_exception::InterfaceException;

/// lo que devuelve un resolver para que su mensaje llegue al cliente: ExecuteGraphQlQueryService la pasa a
/// async-graphql tal cual (el ClientAware de webonyx), con su codigo en extensions.code y su mensaje en
/// extensions.errors (el ProvidesExtensions de webonyx).
/// Las factorias devuelven la excepcion (el never de task-php): el controller la devuelve con Err.
#[derive(Debug, Clone)]
pub struct GraphQlException {
    message: String,
    code: i64,
}

impl GraphQlException {
    /// excepcion controlada (de dominio o FieldsException): su codigo 4xx y su mensaje pasan tal cual al cliente.
    pub fn client_aware_exception_for_webonyx(ex: &dyn InterfaceException) -> Self {
        Self { message: ex.to_string(), code: ex.get_code() }
    }

    /// fallo no controlado: 500 con un mensaje generico, el original no sale (el controller ya lo ha logueado).
    pub fn internal_server_error(message: &str) -> Self {
        Self { message: message.to_string(), code: ExceptionEnum::CODE_INTERNAL_SERVER_ERROR }
    }

    pub fn get_extensions(&self) -> Value {
        json!({
            GraphQlEnum::EXTENSION_CODE: self.code,
            GraphQlEnum::EXTENSION_ERRORS: [self.message],
        })
    }
}

impl InterfaceException for GraphQlException {
    fn get_code(&self) -> i64 {
        self.code
    }
}

impl fmt::Display for GraphQlException {
    fn fmt(&self, formatter: &mut fmt::Formatter<'_>) -> fmt::Result {
        formatter.write_str(&self.message)
    }
}

impl Error for GraphQlException {}
