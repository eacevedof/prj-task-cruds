use crate::core::boot::api_router::Route;
use crate::core::graphql::graphql_controller::GraphqlController;
use crate::modules::open::documentation::infrastructure::controllers::changelog_controller::ChangelogController;
use crate::modules::open::documentation::infrastructure::controllers::documentation_controller::DocumentationController;
use crate::modules::shared::infrastructure::controllers::health_controller::HealthController;
use crate::modules::users::infrastructure::controllers::log_in_user_controller::LogInUserController;

/// rutas de la API (ApiRouter). Url con tags ":nombre" -> args con ese nombre para el controller.
/// allowed: metodos HTTP en minusculas (HEAD va implicito en GET).
/// Solo login (REST) y GraphQL: el resto de operaciones van por /graphql.
/// Auth: /graphql exige "Authorization: Bearer <api_token>" salvo createUser y logIn. No lo decide el router: cada
/// resolver autentica con get_auth_user_or_fail() (AbstractGraphqlController).
pub fn get_routes() -> Vec<Route> {
    vec![
        Route {
            url: "/health",
            allowed: vec!["get"],
            controller: |http_requester, _args| HealthController::get_instance(http_requester).index(),
        },
        //@documentation (publica; fuente: los .md de src/modules/open/documentation/infrastructure/views)
        Route {
            url: "/api/v1",
            allowed: vec!["get"],
            controller: |http_requester, _args| DocumentationController::get_instance(http_requester).show(),
        },
        Route {
            url: "/api/v1/changelog",
            allowed: vec!["get"],
            controller: |http_requester, _args| ChangelogController::get_instance(http_requester).show(),
        },
        //@auth
        Route {
            url: "/api/v1/log-in",
            allowed: vec!["post"],
            controller: |http_requester, _args| LogInUserController::get_instance(http_requester).log_in(),
        },
        //@graphql (schema en src/core/graphql/schema.graphql)
        Route {
            url: "/graphql",
            allowed: vec!["post"],
            controller: |http_requester, _args| GraphqlController::get_instance(http_requester).execute(),
        },
    ]
}
