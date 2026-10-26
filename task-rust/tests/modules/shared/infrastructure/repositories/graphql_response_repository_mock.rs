use serde_json::Value;

use task_rust::modules::shared::application::primitives::{Primitives, to_int, to_string};
use task_rust::modules::shared::domain::enums::graphql_enum::GraphqlEnum;
use task_rust::modules::shared::infrastructure::components::http_responder::http_responder::HttpResponder;

/// lectura de una respuesta GraphQL:
/// {"errors": [{"message", "extensions": {"code", "errors"}}], "data": {...}}
pub struct GraphqlResponseRepositoryMock {
    response_array: Primitives,
}

impl GraphqlResponseRepositoryMock {
    pub fn from_http_response(http_response: &HttpResponder) -> Self {
        let response_array = match serde_json::from_str::<Value>(http_response.get_body()) {
            Ok(Value::Object(response_array)) => response_array,
            _ => Primitives::new(),
        };
        Self { response_array }
    }

    pub fn get_data(&self) -> Primitives {
        self.response_array.get(GraphqlEnum::KEY_DATA).and_then(Value::as_object).cloned().unwrap_or_default()
    }

    pub fn get_errors(&self) -> Vec<Value> {
        self.response_array.get(GraphqlEnum::KEY_ERRORS).and_then(Value::as_array).cloned().unwrap_or_default()
    }

    /// codigo HTTP equivalente REST del primer error (0 si es un error de GraphQL, sin extensions).
    pub fn get_first_error_code(&self) -> i64 {
        to_int(self.get_first_error_extensions().get(GraphqlEnum::EXTENSION_CODE).unwrap_or(&Value::Null))
    }

    pub fn get_first_error_details(&self) -> Vec<String> {
        self.get_first_error_extensions()
            .get(GraphqlEnum::EXTENSION_ERRORS)
            .and_then(Value::as_array)
            .map(|details| details.iter().map(to_string).collect())
            .unwrap_or_default()
    }

    fn get_first_error_extensions(&self) -> Primitives {
        self.get_errors()
            .first()
            .and_then(|first_error| first_error.get(GraphqlEnum::KEY_EXTENSIONS))
            .and_then(Value::as_object)
            .cloned()
            .unwrap_or_default()
    }
}

/// el array_column de PHP: el valor de key en cada elemento de una lista de objetos.
pub fn get_column(list: Option<&Value>, key: &str) -> Vec<Value> {
    list.and_then(Value::as_array)
        .map(|items| {
            items
                .iter()
                .filter_map(|item| item.as_object().map(|object| object.get(key).cloned().unwrap_or(Value::Null)))
                .collect()
        })
        .unwrap_or_default()
}
