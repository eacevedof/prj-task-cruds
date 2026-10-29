use std::sync::Arc;

use task_rust::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use task_rust::modules::users::infrastructure::controllers::log_in_user_rest_controller::LogInUserRestController;

pub fn get_controller_instance(http_requester: Arc<HttpRequester>) -> LogInUserRestController {
    LogInUserRestController::get_instance(http_requester)
}
