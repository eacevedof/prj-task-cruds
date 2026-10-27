use std::sync::Arc;

use serde_json::Value;

use crate::modules::shared::application::primitives::merge_primitives;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::controllers::abstract_graphql_controller::AbstractGraphqlController;
use crate::modules::shared::infrastructure::controllers::graphql_error::GraphqlError;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppError;
use crate::modules::users::application::list_users::list_users_dto::ListUsersDto;
use crate::modules::users::application::list_users::list_users_service::ListUsersService;
use crate::modules::users::domain::exceptions::users_exception::UsersException;

/// query users (src/core/graphql/schema.graphql). Exige Bearer.
pub struct ListUsersApiController {
    abstract_graphql_controller: AbstractGraphqlController,
}

extends!(ListUsersApiController, abstract_graphql_controller, AbstractGraphqlController);

impl ListUsersApiController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_graphql_controller: AbstractGraphqlController::new(http_requester) }
    }

    pub fn invoke(&self) -> Result<Value, GraphqlError> {
        let log_title = "ListUsersApiController.Invoke";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graphql_error(error, log_title))?;
        ListUsersService::get_instance()
            .invoke(ListUsersDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &auth_user_primitives,
            ])))
            .map(|list_users_result_dto| list_users_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, log_title))
    }

    /// los catch de task-php: UsersException es de dominio; lo demas, inesperado.
    fn get_graphql_error(&self, error: AppError, log_title: &str) -> GraphqlError {
        if let Some(users_exception) = error.downcast_ref::<UsersException>() {
            return self.get_graphql_domain_error(users_exception);
        }
        self.get_graphql_unexpected_error(&*error, log_title)
    }
}
