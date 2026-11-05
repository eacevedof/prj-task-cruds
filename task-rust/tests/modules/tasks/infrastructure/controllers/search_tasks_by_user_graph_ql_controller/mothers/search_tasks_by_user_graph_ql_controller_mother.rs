use std::sync::Arc;

use task_rust::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use task_rust::modules::tasks::infrastructure::controllers::search_tasks_by_user_graph_ql_controller::SearchTasksByUserGraphQlController;

pub fn get_controller_instance(http_requester: Arc<HttpRequester>) -> SearchTasksByUserGraphQlController {
    SearchTasksByUserGraphQlController::get_instance(http_requester)
}
