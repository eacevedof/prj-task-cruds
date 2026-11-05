use crate::core::boot::api_router::Route;
use crate::modules::graph_ql::infrastructure::controllers::execute_graph_ql_query_controller::ExecuteGraphQlQueryController;
use crate::modules::open::documentation::infrastructure::controllers::changelog_controller::ChangelogController;
use crate::modules::open::documentation::infrastructure::controllers::documentation_controller::DocumentationController;
use crate::modules::shared::infrastructure::controllers::health_controller::HealthController;
use crate::modules::users::infrastructure::controllers::log_in_user_rest_controller::LogInUserRestController;

pub fn get_routes() -> Vec<Route> {
    vec![
        Route {
            url: "/health",
            allowed: vec!["get"],
            controller: |http_requester, _args| HealthController::get_instance(http_requester).invoke(),
        },
        //@documentation (publica; fuente: los .md de src/modules/open/documentation/infrastructure/views)
        Route {
            url: "/api/v1",
            allowed: vec!["get"],
            controller: |http_requester, _args| DocumentationController::get_instance(http_requester).invoke(),
        },
        Route {
            url: "/api/v1/changelog",
            allowed: vec!["get"],
            controller: |http_requester, _args| ChangelogController::get_instance(http_requester).invoke(),
        },
        //@auth
        Route {
            url: "/api/v1/log-in",
            allowed: vec!["post"],
            controller: |http_requester, _args| LogInUserRestController::get_instance(http_requester).invoke(),
        },
        // @uses ExecuteGraphQlQueryController
        // @graphql (cada modulo registra su XxxGraphQlSchemaProvider en
        // GraphQlSchemaProvidersRegistry::get_all_modules_providers())
        Route {
            url: "/graphql",
            allowed: vec!["post"],
            controller: |http_requester, _args| ExecuteGraphQlQueryController::get_instance(http_requester).invoke(),
        },
    ]
}
