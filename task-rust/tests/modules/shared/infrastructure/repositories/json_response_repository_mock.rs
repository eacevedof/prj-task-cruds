use serde_json::Value;

use task_rust::modules::shared::application::primitives::{Primitives, to_int, to_string};
use task_rust::modules::shared::infrastructure::components::http_responder::http_responder::HttpResponder;

/// lectura del envelope de la API: {"code": int, "status": bool, "errors": string[], "data": array}
pub struct JsonResponseRepositoryMock {
    response_array: Primitives,
}

impl JsonResponseRepositoryMock {
    pub fn from_primitives(response_content: &str) -> Self {
        let response_array = match serde_json::from_str::<Value>(response_content) {
            Ok(Value::Object(response_array)) => response_array,
            _ => Primitives::new(),
        };
        Self { response_array }
    }

    pub fn from_http_response(http_response: &HttpResponder) -> Self {
        Self::from_primitives(http_response.get_body())
    }

    pub fn get_code(&self) -> i64 {
        to_int(self.response_array.get("code").unwrap_or(&Value::Null))
    }

    pub fn get_status(&self) -> bool {
        self.response_array.get("status").and_then(Value::as_bool).unwrap_or(false)
    }

    pub fn get_errors(&self) -> Vec<String> {
        self.response_array
            .get("errors")
            .and_then(Value::as_array)
            .map(|errors| errors.iter().map(to_string).collect())
            .unwrap_or_default()
    }

    pub fn get_data(&self) -> Primitives {
        self.response_array.get("data").and_then(Value::as_object).cloned().unwrap_or_default()
    }

    pub fn get_debug(&self, use_case: &str) {
        let mut debug = String::new();
        if !use_case.is_empty() {
            debug = format!("useCase: {use_case}, ");
        }
        debug += &format!(
            "code: {}, status: {}, errors: {}",
            self.get_code(),
            self.get_status(),
            self.get_errors().join(" | ")
        );
        eprintln!("{debug}");
    }
}
