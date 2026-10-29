use std::sync::Arc;

use serde_json::{Value, json};

use crate::modules::shared::application::primitives::{Primitives, get_primitives, merge_primitives};
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::controllers::abstract_graphql_controller::AbstractGraphqlController;
use crate::modules::shared::infrastructure::controllers::graphql_error::GraphqlError;
use crate::modules::shared::infrastructure::exceptions::fields_exception::FieldsException;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppError;
use crate::modules::users::application::update_user::update_user_dto::UpdateUserDto;
use crate::modules::users::application::update_user::update_user_service::UpdateUserService;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;

/// mutation updateUser (src/core/graphql/schema.graphql). Exige Bearer.
pub struct UpdateUserGraphqlController {
    abstract_graphql_controller: AbstractGraphqlController,
}

extends!(UpdateUserGraphqlController, abstract_graphql_controller, AbstractGraphqlController);

impl UpdateUserGraphqlController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_graphql_controller: AbstractGraphqlController::new(http_requester) }
    }

    pub fn invoke(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        let log_title = "UpdateUserGraphqlController.Invoke";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graphql_error(error, log_title))?;
        UpdateUserService::get_instance()
            .invoke(UpdateUserDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &auth_user_primitives,
                &get_primitives(json!({
                    "userUuid": args.get(UsersKeyEnum::USER_UUID),
                    "userName": args.get(UsersKeyEnum::USER_NAME),
                    "userEmail": args.get(UsersKeyEnum::USER_EMAIL),
                })),
            ])))
            .map(|update_user_result_dto| update_user_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, log_title))
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
