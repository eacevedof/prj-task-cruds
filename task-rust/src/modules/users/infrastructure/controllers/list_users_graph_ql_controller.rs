use std::sync::Arc;

use serde_json::Value;

use crate::core::config::tr;
use crate::modules::graph_ql::domain::exceptions::graph_ql_exception::GraphQlException;
use crate::modules::shared::application::primitives::merge_primitives;
use crate::modules::shared::domain::enums::shared_message_enum::SharedMessageEnum;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::controllers::abstract_http_controller::AbstractHttpController;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppError;
use crate::modules::users::application::list_users::list_users_dto::ListUsersDto;
use crate::modules::users::application::list_users::list_users_service::ListUsersService;
use crate::modules::users::domain::exceptions::users_exception::UsersException;

pub struct ListUsersGraphQlController {
    abstract_http_controller: AbstractHttpController,
}

extends!(ListUsersGraphQlController, abstract_http_controller, AbstractHttpController);

impl ListUsersGraphQlController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_http_controller: AbstractHttpController::new(http_requester) }
    }

    pub fn invoke(&self) -> Result<Value, GraphQlException> {
        let log_title = "ListUsersGraphQlController.Invoke";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graph_ql_exception(error, log_title))?;
        ListUsersService::get_instance()
            .invoke(ListUsersDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &auth_user_primitives,
            ])))
            .map(|list_users_result_dto| list_users_result_dto.to_array())
            .map_err(|error| self.get_graph_ql_exception(error, log_title))
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
