use std::collections::BTreeMap;
use std::io::Cursor;

use tiny_http::{Header, Request, Response, StatusCode};

use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::domain::enums::http_enum::HttpEnum;

/// value object de TRANSPORTE HTTP: lo que un controller devuelve y el front controller (IndexMain) emite. NINGUN
/// controller escribe en la respuesta: construye un HttpResponder y lo devuelve (asi los tests pueden asertar
/// get_status_code() sin emitir nada).
#[derive(Debug, Clone)]
pub struct HttpResponder {
    status_code: i64,
    body: String,
    headers: BTreeMap<String, String>,
}

impl HttpResponder {
    /// body es el JSON ya serializado.
    pub fn json(body: String, status_code: i64, headers: BTreeMap<String, String>) -> Self {
        let mut all_headers =
            BTreeMap::from([(HttpEnum::HEADER_CONTENT_TYPE.to_string(), HttpEnum::CONTENT_TYPE_JSON.to_string())]);
        all_headers.extend(headers);
        Self { status_code, body, headers: all_headers }
    }

    pub fn html(body: String, status_code: i64) -> Self {
        Self {
            status_code,
            body,
            headers: BTreeMap::from([(
                HttpEnum::HEADER_CONTENT_TYPE.to_string(),
                HttpEnum::CONTENT_TYPE_HTML.to_string(),
            )]),
        }
    }

    pub fn no_content() -> Self {
        Self { status_code: ExceptionEnum::CODE_NO_CONTENT, body: String::new(), headers: BTreeMap::new() }
    }

    pub fn get_status_code(&self) -> i64 {
        self.status_code
    }

    pub fn get_body(&self) -> &str {
        &self.body
    }

    pub fn get_headers(&self) -> &BTreeMap<String, String> {
        &self.headers
    }

    /// copia con cabeceras comunes (CORS, seguridad) que pone el front controller; si coinciden, gana la del
    /// controller.
    pub fn get_with_headers(&self, headers: BTreeMap<String, String>) -> Self {
        let mut all_headers = headers;
        all_headers.extend(self.headers.clone());
        Self { status_code: self.status_code, body: self.body.clone(), headers: all_headers }
    }

    /// UNICO punto de emision de toda la app. Lo llama IndexMain.
    pub fn echo_headers_and_body(self, request: Request) {
        let body_length = self.body.len();
        let mut response = Response::new(
            StatusCode(u16::try_from(self.status_code).unwrap_or(500)),
            vec![],
            Cursor::new(self.body.into_bytes()),
            Some(body_length),
            None,
        );
        for (key, value) in &self.headers {
            if let Ok(header) = Header::from_bytes(key.as_bytes(), value.as_bytes()) {
                response.add_header(header);
            }
        }
        let _ = request.respond(response);
    }
}
