use std::error::Error;
use std::fmt;

use crate::core::config::tr;
use crate::modules::graphql::domain::enums::graphql_message_enum::GraphqlMessageEnum;
use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::infrastructure::exceptions::interface_exception::InterfaceException;

/// el registro de /graphql esta mal montado (src/core/config/graphql_schema.rs y los XxxGraphqlSchemaProvider): es un
/// fallo de programacion, no de la peticion, asi que se responde 500 y se loguea.
#[derive(Debug, Clone)]
pub struct GraphqlSchemaException {
    message: String,
    code: i64,
}

impl GraphqlSchemaException {
    pub fn fields_already_have_resolver(type_name: &str, field_names: &[&str], schema_provider_name: &str) -> Self {
        Self {
            message: tr(
                GraphqlMessageEnum::GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X,
                &[&type_name, &field_names.join(", "), &schema_provider_name],
            ),
            code: ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
        }
    }

    pub fn fields_without_resolver(type_name: &str, field_names: &[&str]) -> Self {
        Self {
            message: tr(
                GraphqlMessageEnum::GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER,
                &[&type_name, &field_names.join(", ")],
            ),
            code: ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
        }
    }
}

impl InterfaceException for GraphqlSchemaException {
    fn get_code(&self) -> i64 {
        self.code
    }
}

impl fmt::Display for GraphqlSchemaException {
    fn fmt(&self, formatter: &mut fmt::Formatter<'_>) -> fmt::Result {
        formatter.write_str(&self.message)
    }
}

impl Error for GraphqlSchemaException {}
