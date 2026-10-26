use std::sync::Arc;

use task_rust::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use task_rust::modules::users::infrastructure::controllers::log_in_user_controller::LogInUserController;

pub fn get_controller_instance(http_requester: Arc<HttpRequester>) -> LogInUserController {
    LogInUserController::get_instance(http_requester)
}
