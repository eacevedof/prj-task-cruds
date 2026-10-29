use std::sync::Arc;

use serde_json::json;

use crate::modules::shared::domain::enums::health_key_enum::HealthKeyEnum;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::components::http_responder::http_responder::HttpResponder;
use crate::modules::shared::infrastructure::controllers::abstract_rest_controller::AbstractRestController;

/// GET /health
pub struct HealthController {
    abstract_rest_controller: AbstractRestController,
}

extends!(HealthController, abstract_rest_controller, AbstractRestController);

impl HealthController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_rest_controller: AbstractRestController::new(http_requester) }
    }

    pub fn index(&self) -> HttpResponder {
        self.respond_ok(json!({
            HealthKeyEnum::APP: HealthKeyEnum::APP_NAME,
            HealthKeyEnum::RUST: env!("RUSTC_VERSION"),
            HealthKeyEnum::SQL_DRIVERS: ["sqlite"],
        }))
    }
}
