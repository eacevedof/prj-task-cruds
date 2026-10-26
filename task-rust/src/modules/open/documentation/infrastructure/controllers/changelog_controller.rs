use std::panic::{self, AssertUnwindSafe};
use std::sync::Arc;

use serde_json::json;

use crate::modules::open::documentation::infrastructure::controllers::documentation_controller::get_html_unexpected_error;
use crate::modules::open::documentation::infrastructure::views::changelog_view::ChangelogView;
use crate::modules::open::documentation::infrastructure::views::documentation_view_dto::DocumentationViewDto;
use crate::modules::shared::application::primitives::get_primitives;
use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::components::http_responder::http_responder::HttpResponder;
use crate::modules::shared::infrastructure::repositories::configuration::environment_reader_raw_repository::EnvironmentReaderRawRepository;
use crate::modules::shared::infrastructure::traits::log_trait::LogTrait;

/// GET /api/v1/changelog  (publica, HTML generado desde changelog.md)
pub struct ChangelogController {
    log_trait: LogTrait,
}

impl ChangelogController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { log_trait: LogTrait::new(Some(http_requester)) }
    }

    pub fn show(&self) -> HttpResponder {
        let rendered = panic::catch_unwind(AssertUnwindSafe(|| {
            ChangelogView::get_instance().get_html(&DocumentationViewDto::from_primitives(&get_primitives(json!({
                "appBaseUrl": EnvironmentReaderRawRepository::get_instance().get_base_url(),
            }))))
        }));
        match rendered {
            Ok(html) => HttpResponder::html(html, ExceptionEnum::CODE_OK),
            Err(panic_payload) => {
                get_html_unexpected_error(&self.log_trait, &*panic_payload, "ChangelogController.Show")
            }
        }
    }
}
