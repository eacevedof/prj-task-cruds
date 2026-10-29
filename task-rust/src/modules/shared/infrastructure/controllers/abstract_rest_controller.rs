use std::collections::BTreeMap;
use std::error::Error;
use std::sync::Arc;

use serde_json::{Value, json};

use crate::core::config::tr;
use crate::modules::shared::application::primitives::Primitives;
use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::domain::enums::http_enum::HttpEnum;
use crate::modules::shared::domain::enums::shared_message_enum::SharedMessageEnum;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::components::http_responder::http_responder::HttpResponder;
use crate::modules::shared::infrastructure::controllers::abstract_http_controller::{
    AbstractHttpController, get_json_encoded,
};
use crate::modules::shared::infrastructure::exceptions::interface_exception::InterfaceException;

/// base de los controllers REST. Envelope UNICO de respuesta, con el codigo tambien como status HTTP:
///   {"code": int, "status": bool, "errors": string[], "data": object|array}
#[derive(Debug, Clone)]
pub struct AbstractRestController {
    abstract_http_controller: AbstractHttpController,
}

extends!(AbstractRestController, abstract_http_controller, AbstractHttpController);

impl AbstractRestController {
    pub fn new(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_http_controller: AbstractHttpController::new(http_requester) }
    }

    pub fn respond_ok(&self, data: Value) -> HttpResponder {
        self.get_json_response(ExceptionEnum::CODE_OK, true, vec![], data)
    }

    pub fn respond_errors(&self, code: i64, errors: Vec<String>) -> HttpResponder {
        self.get_json_response(code, false, errors, json!([]))
    }

    /// excepcion controlada (de dominio o FieldsException): su codigo 4xx y su mensaje viajan al cliente tal cual (una
    /// FieldsException ya trae en el mensaje el detalle por campo). El controller la distingue por su tipo.
    pub fn respond_domain_error(&self, ex: &dyn InterfaceException) -> HttpResponder {
        self.respond_errors(ex.get_code(), vec![ex.to_string()])
    }

    /// cualquier otra cosa: 500, se loguea con el metodo que fallo y no filtra su mensaje.
    pub fn respond_unexpected_error(&self, error: &dyn Error, log_title: &str) -> HttpResponder {
        self.log_trait.log_exception(error, log_title);
        self.mail_trait.send_mail_on_throwable(error, log_title);
        self.respond_errors(
            ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
            vec![tr(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED, &[])],
        )
    }

    fn get_json_response(&self, code: i64, status: bool, errors: Vec<String>, data: Value) -> HttpResponder {
        let mut headers = BTreeMap::new();
        // RFC 6750: un 401 indica al cliente el esquema que se espera
        if code == ExceptionEnum::CODE_UNAUTHORIZED {
            headers.insert(HttpEnum::HEADER_WWW_AUTHENTICATE.to_string(), HttpEnum::AUTH_SCHEME_BEARER.to_string());
        }
        HttpResponder::json(get_json_encoded(&get_json_envelope(code, status, errors, data)), code, headers)
    }
}

/// {code, status, errors, data}: las claves de HttpEnum en ese orden, como en task-php.
pub fn get_json_envelope(code: i64, status: bool, errors: Vec<String>, data: Value) -> Value {
    let mut json_envelope = Primitives::new();
    json_envelope.insert(HttpEnum::KEY_CODE.to_string(), json!(code));
    json_envelope.insert(HttpEnum::KEY_STATUS.to_string(), json!(status));
    json_envelope.insert(HttpEnum::KEY_ERRORS.to_string(), json!(errors));
    json_envelope.insert(HttpEnum::KEY_DATA.to_string(), data);
    Value::Object(json_envelope)
}
