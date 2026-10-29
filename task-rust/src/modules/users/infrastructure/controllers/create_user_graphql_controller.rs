use std::sync::Arc;

use serde_json::{Value, json};

use crate::modules::graphql::infrastructure::controllers::abstract_graphql_controller::AbstractGraphqlController;
use crate::modules::graphql::infrastructure::controllers::graphql_error::GraphqlError;
use crate::modules::shared::application::primitives::{Primitives, get_primitives, merge_primitives};
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::exceptions::fields_exception::FieldsException;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppError;
use crate::modules::users::application::create_user::create_user_dto::CreateUserDto;
use crate::modules::users::application::create_user::create_user_service::CreateUserService;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;

/// mutation createUser (src/modules/users/infrastructure/providers/users.graphql): alta abierta (sin token), devuelve el
/// primer api_token.
pub struct CreateUserGraphqlController {
    abstract_graphql_controller: AbstractGraphqlController,
}

extends!(CreateUserGraphqlController, abstract_graphql_controller, AbstractGraphqlController);

impl CreateUserGraphqlController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_graphql_controller: AbstractGraphqlController::new(http_requester) }
    }

    pub fn invoke(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        CreateUserService::get_instance()
            .invoke(CreateUserDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &get_primitives(json!({
                    "userName": args.get(UsersKeyEnum::USER_NAME),
                    "userEmail": args.get(UsersKeyEnum::USER_EMAIL),
                    "password": args.get(UsersKeyEnum::PASSWORD),
                })),
            ])))
            .map(|create_user_result_dto| create_user_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, "CreateUserGraphqlController.Invoke"))
    }

    /// los catch de task-php: FieldsException y UsersException son de dominio; lo demas, inesperado.
    fn get_graphql_error(&self, error: AppError, log_title: &str) -> GraphqlError {
        if let Some(fields_exception) = error.downcast_ref::<FieldsException>() {
            return self.get_graphql_domain_error(fields_exception);
        }
        if let Some(users_exception) = error.downcast_ref::<UsersException>() {
            return self.get_graphql_domain_error(users_exception);
        }
        self.get_graphql_unexpected_error(&*error, log_title)
    }
}
