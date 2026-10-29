use crate::modules::graphql::domain::enums::graphql_enum::GraphqlEnum;
use crate::modules::graphql::infrastructure::schema::interface_graphql_schema_provider::{
    InterfaceGraphqlSchemaProvider, ResolversByType,
};
use crate::modules::users::infrastructure::controllers::create_user_graphql_controller::CreateUserGraphqlController;
use crate::modules::users::infrastructure::controllers::delete_user_graphql_controller::DeleteUserGraphqlController;
use crate::modules::users::infrastructure::controllers::get_user_graphql_controller::GetUserGraphqlController;
use crate::modules::users::infrastructure::controllers::list_users_graphql_controller::ListUsersGraphqlController;
use crate::modules::users::infrastructure::controllers::log_in_user_graphql_controller::LogInUserGraphqlController;
use crate::modules::users::infrastructure::controllers::regenerate_user_api_token_graphql_controller::RegenerateUserApiTokenGraphqlController;
use crate::modules::users::infrastructure::controllers::update_user_graphql_controller::UpdateUserGraphqlController;

/// parte de users en /graphql: users.graphql (define Query, Mutation y User) y sus resolvers. El resolver se crea
/// solo si la consulta pide su campo.
pub struct UsersGraphqlSchemaProvider;

impl InterfaceGraphqlSchemaProvider for UsersGraphqlSchemaProvider {
    fn get_instance() -> Self {
        Self
    }

    fn get_sdl(&self) -> &'static str {
        include_str!("users.graphql")
    }

    fn get_resolvers_by_type(&self) -> ResolversByType {
        vec![
            (
                GraphqlEnum::TYPE_QUERY,
                vec![
                    ("users", |http_requester, _args, _user| {
                        ListUsersGraphqlController::get_instance(http_requester).invoke()
                    }),
                    ("user", |http_requester, args, _user| {
                        GetUserGraphqlController::get_instance(http_requester).invoke(args)
                    }),
                ],
            ),
            (
                GraphqlEnum::TYPE_MUTATION,
                vec![
                    ("createUser", |http_requester, args, _user| {
                        CreateUserGraphqlController::get_instance(http_requester).invoke(args)
                    }),
                    ("logIn", |http_requester, args, _user| {
                        LogInUserGraphqlController::get_instance(http_requester).invoke(args)
                    }),
                    ("updateUser", |http_requester, args, _user| {
                        UpdateUserGraphqlController::get_instance(http_requester).invoke(args)
                    }),
                    ("deleteUser", |http_requester, args, _user| {
                        DeleteUserGraphqlController::get_instance(http_requester).invoke(args)
                    }),
                    ("regenerateApiToken", |http_requester, args, _user| {
                        RegenerateUserApiTokenGraphqlController::get_instance(http_requester).invoke(args)
                    }),
                ],
            ),
        ]
    }
}
