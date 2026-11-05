use std::sync::Arc;

use serde_json::{Value, json};

use crate::core::config::tr;
use crate::modules::graph_ql::domain::exceptions::graph_ql_exception::GraphQlException;
use crate::modules::shared::application::primitives::{Primitives, get_primitives, merge_primitives};
use crate::modules::shared::domain::enums::shared_message_enum::SharedMessageEnum;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::controllers::abstract_http_controller::AbstractHttpController;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppError;
use crate::modules::users::application::log_in_user::log_in_user_dto::LogInUserDto;
use crate::modules::users::application::log_in_user::log_in_user_service::LogInUserService;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::exceptions::users_exception::UsersException;

pub struct LogInUserGraphQlController {
    abstract_http_controller: AbstractHttpController,
}

extends!(LogInUserGraphQlController, abstract_http_controller, AbstractHttpController);

impl LogInUserGraphQlController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_http_controller: AbstractHttpController::new(http_requester) }
    }

    pub fn invoke(&self, gql_payload: &Primitives) -> Result<Value, GraphQlException> {
        LogInUserService::get_instance()
            .invoke(LogInUserDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &get_primitives(json!({
                    "userName": gql_payload.get(UsersKeyEnum::USER_NAME),
                    "password": gql_payload.get(UsersKeyEnum::PASSWORD),
                })),
            ])))
            .map(|log_in_user_result_dto| log_in_user_result_dto.to_array())
            .map_err(|error| self.get_graph_ql_exception(error, "LogInUserGraphQlController.Invoke"))
    }

    /// los dos catch de task-php: UsersException es de dominio (GraphQlException con su codigo); lo demas, inesperado
    /// (log + mail + 500).
    fn get_graph_ql_exception(&self, error: AppError, log_title: &str) -> GraphQlException {
        if let Some(users_exception) = error.downcast_ref::<UsersException>() {
            return GraphQlException::client_aware_exception_for_webonyx(users_exception);
        }
        self.log_trait.log_exception(&*error, log_title);
        self.mail_trait.send_mail_on_throwable(&*error, log_title);
        GraphQlException::internal_server_error(&tr(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED, &[]))
    }
}
