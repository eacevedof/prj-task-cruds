use serde_json::Value;

/// el GraphQL\Error de webonyx: el error que devuelve un resolver. El resolver lo pasa a async-graphql, que lo pone en
/// errors[] con su message y extensions.code (codigo HTTP equivalente) + extensions.errors (el detalle).
#[derive(Debug, Clone)]
pub struct GraphqlError {
    message: String,
    extensions: Value,
}

impl GraphqlError {
    pub fn new(message: String, extensions: Value) -> Self {
        Self { message, extensions }
    }

    pub fn get_message(&self) -> &str {
        &self.message
    }

    pub fn get_extensions(&self) -> &Value {
        &self.extensions
    }
}
