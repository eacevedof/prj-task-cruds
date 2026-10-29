use serde_json::{Value, json};

use crate::modules::graphql::domain::enums::graphql_enum::GraphqlEnum;
use crate::modules::shared::application::primitives::Primitives;

/// respuesta GraphQL {errors?, data?}: las claves solo van si traen algo y en el orden de webonyx (errors primero).
#[derive(Debug, Clone)]
pub struct GraphqlResultDto {
    errors: Vec<Value>,
    data: Option<Value>,
}

impl GraphqlResultDto {
    /// graphql_result: el ExecutionResult::toArray() de webonyx ({errors?, data?}), ya como JSON.
    pub fn from_primitives(graphql_result: &Primitives) -> Self {
        Self {
            errors: graphql_result.get(GraphqlEnum::KEY_ERRORS).and_then(Value::as_array).cloned().unwrap_or_default(),
            data: graphql_result.get(GraphqlEnum::KEY_DATA).filter(|data| !data.is_null()).cloned(),
        }
    }

    /// error antes de ejecutar (peticion sin query, fallo inesperado): sin data, como lo daria GraphQL.
    pub fn from_error_message(message: &str) -> Self {
        Self { errors: vec![json!({GraphqlEnum::KEY_MESSAGE: message})], data: None }
    }

    pub fn to_array(&self) -> Value {
        let mut graphql_result = Primitives::new();
        if !self.errors.is_empty() {
            graphql_result.insert(GraphqlEnum::KEY_ERRORS.to_string(), Value::Array(self.errors.clone()));
        }
        if let Some(data) = &self.data {
            graphql_result.insert(GraphqlEnum::KEY_DATA.to_string(), data.clone());
        }
        Value::Object(graphql_result)
    }
}
