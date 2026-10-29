use std::sync::Arc;

use serde_json::{Value, json};

use crate::modules::shared::application::primitives::{Primitives, get_primitives, merge_primitives};
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::controllers::abstract_graphql_controller::AbstractGraphqlController;
use crate::modules::shared::infrastructure::controllers::graphql_error::GraphqlError;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppError;
use crate::modules::users::application::log_in_user::log_in_user_dto::LogInUserDto;
use crate::modules::users::application::log_in_user::log_in_user_service::LogInUserService;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;

/// mutation logIn (src/core/graphql/schema.graphql). Publico (sin token): user_name + password -> el usuario con
/// un api_token nuevo. El mismo caso de uso que POST /api/v1/log-in (LogInUserRestController).
pub struct LogInUserGraphqlController {
    abstract_graphql_controller: AbstractGraphqlController,
}

extends!(LogInUserGraphqlController, abstract_graphql_controller, AbstractGraphqlController);

impl LogInUserGraphqlController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_graphql_controller: AbstractGraphqlController::new(http_requester) }
    }

    pub fn invoke(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        LogInUserService::get_instance()
            .invoke(LogInUserDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &get_primitives(json!({
                    "userName": args.get(UsersKeyEnum::USER_NAME),
                    "password": args.get(UsersKeyEnum::PASSWORD),
                })),
            ])))
            .map(|log_in_user_result_dto| log_in_user_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, "LogInUserGraphqlController.Invoke"))
    }

    /// los catch de task-php: UsersException es de dominio; lo demas, inesperado.
    fn get_graphql_error(&self, error: AppError, log_title: &str) -> GraphqlError {
        if let Some(users_exception) = error.downcast_ref::<UsersException>() {
            return self.get_graphql_domain_error(users_exception);
        }
        self.get_graphql_unexpected_error(&*error, log_title)
    }
}
