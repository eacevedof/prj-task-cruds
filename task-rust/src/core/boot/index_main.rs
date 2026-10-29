use std::backtrace::Backtrace;
use std::collections::BTreeMap;
use std::error::Error;
use std::panic::{self, AssertUnwindSafe};
use std::sync::Arc;

use serde_json::{Value, json};

use crate::core::boot::api_router::{ApiRouter, Route};
use crate::core::boot::sensitive_data_masker::SensitiveDataMasker;
use crate::core::config::lgerr;
use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::{HttpRequest, HttpRequester};
use crate::modules::shared::infrastructure::components::http_responder::http_responder::HttpResponder;
use crate::modules::shared::infrastructure::components::uuid::uuid_generator::UuidGenerator;
use crate::modules::shared::infrastructure::controllers::abstract_http_controller::get_json_encoded;
use crate::modules::shared::infrastructure::controllers::abstract_rest_controller::get_json_envelope;
use crate::modules::shared::infrastructure::exceptions::interface_exception::get_panic_error;
use crate::modules::shared::infrastructure::repositories::configuration::environment_reader_raw_repository::EnvironmentReaderRawRepository;

/// front controller de la API (el public/index.php de task-php). Sin sesion ni vistas: solo JSON.
pub struct IndexMain {
    routes: Vec<Route>,
}

impl IndexMain {
    pub fn get_instance(routes: Vec<Route>) -> Self {
        Self { routes }
    }

    /// la respuesta de una peticion. El panic que nadie ha tratado acaba aqui: 500 generico con un id.
    pub fn get_http_responder(&self, http_request: HttpRequest) -> HttpResponder {
        let mut http_requester = HttpRequester::get_instance(http_request);
        let http_responder = panic::catch_unwind(AssertUnwindSafe(|| self.exec(&mut http_requester)));
        match http_responder {
            Ok(http_responder) => http_responder,
            Err(panic_payload) => {
                let error = get_panic_error(&*panic_payload);
                debug_error(&http_requester, &*error);
                on_error(&http_requester, &*error)
            }
        }
    }

    fn exec(&self, http_requester: &mut HttpRequester) -> HttpResponder {
        let request_method = http_requester.get_request_method();
        if request_method == "options" {
            return get_with_common_headers(http_requester, HttpResponder::no_content());
        }

        let run_data =
            ApiRouter::get_instance(&self.routes).get_run_data(&request_method, &http_requester.get_request_uri());
        let Some(route) = run_data.route else {
            if run_data.allowed.is_empty() {
                return get_with_common_headers(
                    http_requester,
                    get_error_response(ExceptionEnum::CODE_NOT_FOUND, "Resource not found", BTreeMap::new()),
                );
            }
            return get_with_common_headers(
                http_requester,
                get_error_response(
                    ExceptionEnum::CODE_METHOD_NOT_ALLOWED,
                    "Method not allowed",
                    BTreeMap::from([("Allow".to_string(), run_data.allowed.join(", ").to_uppercase())]),
                ),
            );
        };

        http_requester.load_json_body_into_post();
        let http_responder = (route.controller)(Arc::new(http_requester.clone()), &run_data.args);
        get_with_common_headers(http_requester, http_responder)
    }
}

/// toda respuesta sale por aqui: las cabeceras comunes se suman a las del HttpResponder y se emiten juntas en
/// echo_headers_and_body(), el unico punto que escribe la respuesta.
fn get_with_common_headers(http_requester: &HttpRequester, http_responder: HttpResponder) -> HttpResponder {
    let mut common_headers = get_cors_headers(http_requester);
    common_headers.extend(get_security_headers());
    http_responder.get_with_headers(common_headers)
}

fn get_cors_headers(http_requester: &HttpRequester) -> BTreeMap<String, String> {
    let mut cors_headers = BTreeMap::new();
    let origin = http_requester.get_header_value_by_key("Origin");
    if !origin.is_empty() {
        cors_headers.insert("Access-Control-Allow-Origin".to_string(), origin);
        cors_headers.insert("Access-Control-Max-Age".to_string(), "86400".to_string());
    }
    if http_requester.get_request_method() == "options" {
        cors_headers.insert("Access-Control-Allow-Methods".to_string(), "GET, POST, PUT, DELETE, OPTIONS".to_string());
        cors_headers.insert(
            "Access-Control-Allow-Headers".to_string(),
            "Content-Type, Accept, Accept-Language, Authorization, X-Time-Zone".to_string(),
        );
    }
    cors_headers
}

fn get_security_headers() -> BTreeMap<String, String> {
    BTreeMap::from([
        ("X-Content-Type-Options".to_string(), "nosniff".to_string()),
        ("Referrer-Policy".to_string(), "no-referrer".to_string()),
    ])
}

/// el panic que nadie ha tratado: se loguea con un id y el cliente recibe ese id en un 500 generico.
fn on_error(http_requester: &HttpRequester, error: &dyn Error) -> HttpResponder {
    let error_uuid = UuidGenerator::get_instance().get_unique_id_with_prefix("");
    let sensitive_data_masker = SensitiveDataMasker::get_instance();
    let post = http_requester.get_post();
    if !post.is_empty() {
        let masked_post = Value::Object(sensitive_data_masker.get_masked_array(post));
        lgerr(&masked_post.to_string(), &format!("index-exception {error_uuid} POST"), "error");
    }
    let get = http_requester.get_get();
    if !get.is_empty() {
        let masked_get = Value::Object(sensitive_data_masker.get_masked_array(&get));
        lgerr(&masked_get.to_string(), &format!("index-exception {error_uuid} GET"), "error");
    }
    lgerr(&error.to_string(), &format!("index-exception {error_uuid}"), "error");
    lgerr(&Backtrace::force_capture().to_string(), &format!("index-exception {error_uuid} TRACE"), "error");

    get_with_common_headers(
        http_requester,
        HttpResponder::json(
            get_json_encoded(&get_json_envelope(
                ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
                false,
                vec![format!("Sorry! but some unexpected error occurred ({error_uuid})")],
                json!([]),
            )),
            ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
            BTreeMap::new(),
        ),
    )
}

fn debug_error(http_requester: &HttpRequester, error: &dyn Error) {
    let environment_reader_raw_repository = EnvironmentReaderRawRepository::get_instance();
    if environment_reader_raw_repository.is_production() || !environment_reader_raw_repository.is_debug() {
        return;
    }

    let debug = json!({
        "Exception": error.to_string(),
        "POST": http_requester.get_post(),
        "GET": http_requester.get_get(),
    });
    lgerr(&debug.to_string(), "index-debug", "debug");
}

fn get_error_response(code: i64, error_message: &str, headers: BTreeMap<String, String>) -> HttpResponder {
    HttpResponder::json(
        get_json_encoded(&get_json_envelope(code, false, vec![error_message.to_string()], json!([]))),
        code,
        headers,
    )
}
