use serde_json::Value;

use crate::modules::graphql::domain::enums::graphql_enum::GraphqlEnum;
use crate::modules::shared::application::primitives::{Primitives, to_string};

/// body de POST /graphql: {query, variables?, operationName?}.
#[derive(Debug, Clone)]
pub struct GraphqlRequestDto {
    query: String,
    variables: Primitives,
    operation_name: Option<String>,
}

impl GraphqlRequestDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        let operation_name = get_scalar_as_string(primitives.get(GraphqlEnum::KEY_OPERATION_NAME));
        Self {
            query: get_scalar_as_string(primitives.get(GraphqlEnum::KEY_QUERY)),
            variables: get_variables_as_primitives(primitives.get(GraphqlEnum::KEY_VARIABLES)),
            operation_name: if operation_name.is_empty() { None } else { Some(operation_name) },
        }
    }

    pub fn get_query(&self) -> &str {
        &self.query
    }

    pub fn get_variables(&self) -> &Primitives {
        &self.variables
    }

    pub fn get_operation_name(&self) -> Option<&str> {
        self.operation_name.as_deref()
    }
}

/// un array/objeto donde va un string cuenta como vacio (como HttpRequester::get_post_string), no como su JSON.
fn get_scalar_as_string(value: Option<&Value>) -> String {
    match value {
        None | Some(Value::Array(_)) | Some(Value::Object(_)) => String::new(),
        Some(scalar) => to_string(scalar).trim().to_string(),
    }
}

/// "variables" puede llegar como objeto JSON o, en algunos clientes, como string JSON.
fn get_variables_as_primitives(variables: Option<&Value>) -> Primitives {
    match variables {
        Some(Value::Object(variables)) => variables.clone(),
        Some(Value::String(json_variables)) => match serde_json::from_str::<Value>(json_variables) {
            Ok(Value::Object(variables)) => variables,
            _ => Primitives::new(),
        },
        _ => Primitives::new(),
    }
}
