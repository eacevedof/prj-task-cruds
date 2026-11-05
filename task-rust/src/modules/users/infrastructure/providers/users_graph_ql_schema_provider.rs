use crate::modules::graph_ql::domain::enums::graph_ql_enum::GraphQlEnum;
use crate::modules::graph_ql::infrastructure::providers::interface_graph_ql_schema_provider::{
    FieldResolvers, InterfaceGraphQlSchemaProvider, ResolversByType,
};
use crate::modules::users::infrastructure::controllers::create_user_graph_ql_controller::CreateUserGraphQlController;
use crate::modules::users::infrastructure::controllers::delete_user_graph_ql_controller::DeleteUserGraphQlController;
use crate::modules::users::infrastructure::controllers::get_user_graph_ql_controller::GetUserGraphQlController;
use crate::modules::users::infrastructure::controllers::list_users_graph_ql_controller::ListUsersGraphQlController;
use crate::modules::users::infrastructure::controllers::log_in_user_graph_ql_controller::LogInUserGraphQlController;
use crate::modules::users::infrastructure::controllers::regenerate_user_api_token_graph_ql_controller::RegenerateUserApiTokenGraphQlController;
use crate::modules::users::infrastructure::controllers::update_user_graph_ql_controller::UpdateUserGraphQlController;

/// parte de users en /graphql: users.graphql (define Query, Mutation y User) y sus resolvers. El resolver se crea
/// solo si la consulta pide su campo.
pub struct UsersGraphQlSchemaProvider;

impl UsersGraphQlSchemaProvider {
    /// tipo que define users.graphql y extienden otros modulos (Tasks le anade tasks)
    pub const TYPE_USER: &'static str = "User";

    fn get_query_controllers(&self) -> FieldResolvers {
        vec![
            ("users", |http_requester, _gql_payload, _user| {
                ListUsersGraphQlController::get_instance(http_requester).invoke()
            }),
            ("user", |http_requester, gql_payload, _user| {
                GetUserGraphQlController::get_instance(http_requester).invoke(gql_payload)
            }),
        ]
    }

    fn get_mutation_controllers(&self) -> FieldResolvers {
        vec![
            ("createUser", |http_requester, gql_payload, _user| {
                CreateUserGraphQlController::get_instance(http_requester).invoke(gql_payload)
            }),
            ("logIn", |http_requester, gql_payload, _user| {
                LogInUserGraphQlController::get_instance(http_requester).invoke(gql_payload)
            }),
            ("updateUser", |http_requester, gql_payload, _user| {
                UpdateUserGraphQlController::get_instance(http_requester).invoke(gql_payload)
            }),
            ("deleteUser", |http_requester, gql_payload, _user| {
                DeleteUserGraphQlController::get_instance(http_requester).invoke(gql_payload)
            }),
            ("regenerateApiToken", |http_requester, gql_payload, _user| {
                RegenerateUserApiTokenGraphQlController::get_instance(http_requester).invoke(gql_payload)
            }),
        ]
    }
}

impl InterfaceGraphQlSchemaProvider for UsersGraphQlSchemaProvider {
    fn get_instance() -> Self {
        Self
    }

    fn get_graph_ql_file_content(&self) -> &'static str {
        include_str!("users.graphql")
    }

    fn get_controller_resolvers(&self) -> ResolversByType {
        vec![
            (GraphQlEnum::TYPE_QUERY, self.get_query_controllers()),
            (GraphQlEnum::TYPE_MUTATION, self.get_mutation_controllers()),
        ]
    }
}
