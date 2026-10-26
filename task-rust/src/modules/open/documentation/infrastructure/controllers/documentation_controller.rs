use std::any::Any;
use std::panic::{self, AssertUnwindSafe};
use std::sync::Arc;

use serde_json::json;

use crate::core::config::tr;
use crate::modules::open::documentation::infrastructure::views::documentation_view::DocumentationView;
use crate::modules::open::documentation::infrastructure::views::documentation_view_dto::DocumentationViewDto;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::domain::enums::shared_message_enum::SharedMessageEnum;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::components::http_responder::http_responder::HttpResponder;
use crate::modules::shared::infrastructure::exceptions::interface_exception::get_panic_error;
use crate::modules::shared::infrastructure::repositories::configuration::environment_reader_raw_repository::EnvironmentReaderRawRepository;
use crate::modules::shared::infrastructure::traits::log_trait::LogTrait;

/// GET /api/v1  (publica, HTML generado desde documentation.md)
pub struct DocumentationController {
    log_trait: LogTrait,
}

impl DocumentationController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { log_trait: LogTrait::new(Some(http_requester)) }
    }

    /// el render no devuelve error: lo unico que puede fallar es un panic, y se responde como el catch de task-php
    pub fn show(&self) -> HttpResponder {
        let rendered = panic::catch_unwind(AssertUnwindSafe(|| {
            let environment_reader_raw_repository = EnvironmentReaderRawRepository::get_instance();
            DocumentationView::get_instance().get_html(&DocumentationViewDto::from_primitives(&get_primitives(json!({
                "appVersion": environment_reader_raw_repository.get_app_version(),
                "appVersionUpdate": environment_reader_raw_repository.get_app_version_update(),
                "appBaseUrl": environment_reader_raw_repository.get_base_url(),
            }))))
        }));
        match rendered {
            Ok(html) => HttpResponder::html(html, ExceptionEnum::CODE_OK),
            Err(panic_payload) => {
                get_html_unexpected_error(&self.log_trait, &*panic_payload, "DocumentationController.Show")
            }
        }
    }
}

pub fn get_html_unexpected_error(
    log_trait: &LogTrait,
    panic_payload: &(dyn Any + Send),
    log_title: &str,
) -> HttpResponder {
    log_trait.log_exception(&*get_panic_error(panic_payload), log_title);
    HttpResponder::html(
        format!("<h1>{}</h1>", tr(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED, &[])),
        ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
    )
}
