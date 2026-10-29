use std::sync::Arc;

use task_rust::modules::graphql::infrastructure::controllers::graphql_controller::GraphqlController;
use task_rust::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;

pub fn get_controller_instance(http_requester: Arc<HttpRequester>) -> GraphqlController {
    GraphqlController::get_instance(http_requester)
}
