use std::sync::Arc;

use serde_json::{Value, json};

use crate::core::config::tr;
use crate::modules::graph_ql::domain::exceptions::graph_ql_exception::GraphQlException;
use crate::modules::shared::application::primitives::{Primitives, get_primitives, merge_primitives};
use crate::modules::shared::domain::enums::shared_message_enum::SharedMessageEnum;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::controllers::abstract_http_controller::AbstractHttpController;
use crate::modules::shared::infrastructure::exceptions::fields_exception::FieldsException;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppError;
use crate::modules::tasks::application::update_task::update_task_dto::UpdateTaskDto;
use crate::modules::tasks::application::update_task::update_task_service::UpdateTaskService;
use crate::modules::tasks::domain::enums::tasks_key_enum::TasksKeyEnum;
use crate::modules::tasks::domain::exceptions::tasks_exception::TasksException;
use crate::modules::users::domain::exceptions::users_exception::UsersException;

/// mutation updateTask (src/modules/tasks/infrastructure/providers/tasks.graphql). El 401 de la autenticacion llega como
/// UsersException.
pub struct UpdateTaskGraphQlController {
    abstract_http_controller: AbstractHttpController,
}

extends!(UpdateTaskGraphQlController, abstract_http_controller, AbstractHttpController);

impl UpdateTaskGraphQlController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_http_controller: AbstractHttpController::new(http_requester) }
    }

    pub fn invoke(&self, gql_payload: &Primitives) -> Result<Value, GraphQlException> {
        let log_title = "UpdateTaskGraphQlController.Invoke";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graph_ql_exception(error, log_title))?;
        UpdateTaskService::get_instance()
            .invoke(UpdateTaskDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &auth_user_primitives,
                &get_primitives(json!({
                    "taskUuid": gql_payload.get(TasksKeyEnum::TASK_UUID),
                    "taskTitle": gql_payload.get(TasksKeyEnum::TASK_TITLE),
                    "taskStatus": gql_payload.get(TasksKeyEnum::TASK_STATUS),
                    "taskType": gql_payload.get(TasksKeyEnum::TASK_TYPE),
                    "targetDate": gql_payload.get(TasksKeyEnum::TARGET_DATE),
                    "taskDescription": gql_payload.get(TasksKeyEnum::TASK_DESCRIPTION),
                })),
            ])))
            .map(|update_task_result_dto| update_task_result_dto.to_array())
            .map_err(|error| self.get_graph_ql_exception(error, log_title))
    }

    /// los dos catch de task-php: FieldsException, UsersException y TasksException son de dominio (GraphQlException con
    /// su codigo); lo demas, inesperado (log + mail + 500).
    fn get_graph_ql_exception(&self, error: AppError, log_title: &str) -> GraphQlException {
        if let Some(fields_exception) = error.downcast_ref::<FieldsException>() {
            return GraphQlException::client_aware_exception_for_webonyx(fields_exception);
        }
        if let Some(users_exception) = error.downcast_ref::<UsersException>() {
            return GraphQlException::client_aware_exception_for_webonyx(users_exception);
        }
        if let Some(tasks_exception) = error.downcast_ref::<TasksException>() {
            return GraphQlException::client_aware_exception_for_webonyx(tasks_exception);
        }
        self.log_trait.log_exception(&*error, log_title);
        self.mail_trait.send_mail_on_throwable(&*error, log_title);
        GraphQlException::internal_server_error(&tr(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED, &[]))
    }
}
