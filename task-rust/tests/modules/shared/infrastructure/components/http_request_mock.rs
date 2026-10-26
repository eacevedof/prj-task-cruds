use std::collections::HashMap;
use std::sync::Arc;

use serde_json::Value;

use task_rust::modules::shared::application::primitives::Primitives;
use task_rust::modules::shared::infrastructure::components::http_requester::http_requester::{
    HttpRequest, HttpRequester,
};

/// peticion simulada: cabeceras (el Bearer que lee get_auth_user_or_fail, User-Agent / Accept-Language / X-Time-Zone
/// que lee HttpRequester), ip, body JSON y query. get_mocked_instance() devuelve el HttpRequester ya con el body
/// cargado (lo que hace IndexMain) y los tags de la url (":userUuid") para el controller.
#[derive(Default)]
pub struct HttpRequestMock {
    bearer_token: String,
    ip_address: String,
    headers: Vec<(String, String)>,
    route_params: HashMap<String, String>,
    query: Vec<(String, String)>,
    post_raw: Option<Primitives>,
}

impl HttpRequestMock {
    pub fn get_instance() -> Self {
        Self::default()
    }

    pub fn set_bearer_token(mut self, bearer_token: &str) -> Self {
        self.bearer_token = bearer_token.to_string();
        self
    }

    pub fn set_ip_address(mut self, ip_address: &str) -> Self {
        self.ip_address = ip_address.to_string();
        self
    }

    /// cabeceras con su nombre HTTP: [("User-Agent", "..."), ("Accept-Language", "es-ES")].
    pub fn set_headers(mut self, headers: &[(&str, &str)]) -> Self {
        self.headers = headers.iter().map(|(name, value)| (name.to_string(), value.to_string())).collect();
        self
    }

    pub fn set_route_params(mut self, route_params: HashMap<String, String>) -> Self {
        self.route_params = route_params;
        self
    }

    pub fn set_get_params(mut self, query_params: &[(&str, &str)]) -> Self {
        self.query = query_params.iter().map(|(key, value)| (key.to_string(), value.to_string())).collect();
        self
    }

    pub fn set_post_raw_params(mut self, post_params: Primitives) -> Self {
        self.post_raw = Some(post_params);
        self
    }

    /// el HttpRequester de la peticion y los argumentos con nombre del controller.
    pub fn get_mocked_instance(self) -> (Arc<HttpRequester>, HashMap<String, String>) {
        let mut headers = vec![("Content-Type".to_string(), "application/json".to_string())];
        if !self.bearer_token.is_empty() {
            headers.push(("Authorization".to_string(), format!("Bearer {}", self.bearer_token)));
        }
        headers.extend(self.headers);

        let query_string: Vec<String> = self.query.iter().map(|(key, value)| format!("{key}={value}")).collect();
        let request_uri =
            if query_string.is_empty() { "/".to_string() } else { format!("/?{}", query_string.join("&")) };
        let body = self.post_raw.map(|post_raw| Value::Object(post_raw).to_string().into_bytes()).unwrap_or_default();

        let mut http_requester = HttpRequester::get_instance(HttpRequest {
            method: "POST".to_string(),
            request_uri,
            headers,
            body,
            remote_addr: self.ip_address,
        });
        http_requester.load_json_body_into_post();
        (Arc::new(http_requester), self.route_params)
    }
}
