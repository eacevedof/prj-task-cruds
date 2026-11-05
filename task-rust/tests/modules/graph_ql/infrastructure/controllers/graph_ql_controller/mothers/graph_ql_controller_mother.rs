use std::sync::Arc;

use task_rust::modules::graph_ql::infrastructure::controllers::execute_graph_ql_query_controller::ExecuteGraphQlQueryController;
use task_rust::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;

pub fn get_controller_instance(http_requester: Arc<HttpRequester>) -> ExecuteGraphQlQueryController {
    ExecuteGraphQlQueryController::get_instance(http_requester)
}
