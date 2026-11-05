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
use crate::modules::tasks::application::search_tasks::search_tasks_dto::SearchTasksDto;
use crate::modules::tasks::application::search_tasks::search_tasks_service::SearchTasksService;
use crate::modules::tasks::domain::enums::tasks_key_enum::TasksKeyEnum;
use crate::modules::tasks::domain::exceptions::tasks_exception::TasksException;
use crate::modules::users::domain::exceptions::users_exception::UsersException;

/// campo User.tasks (src/modules/tasks/infrastructure/providers/tasks.graphql): las tasks de un usuario, paginadas y
/// con filtros. Solo ejemplo, no lo consume ningun front. El uuid llega aparte porque sale del User padre, no del
/// payload. Un usuario que no es el autenticado responde 404. El 401 de la autenticacion llega como UsersException.
pub struct SearchTasksByUserGraphQlController {
    abstract_http_controller: AbstractHttpController,
}

extends!(SearchTasksByUserGraphQlController, abstract_http_controller, AbstractHttpController);

impl SearchTasksByUserGraphQlController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_http_controller: AbstractHttpController::new(http_requester) }
    }

    /// gql_payload {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?,
    /// page_size?}
    pub fn invoke(&self, user_uuid: &str, gql_payload: &Primitives) -> Result<Value, GraphQlException> {
        let log_title = "SearchTasksByUserGraphQlController.Invoke";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graph_ql_exception(error, log_title))?;
        let filter =
            gql_payload.get(TasksKeyEnum::FILTER).and_then(|filter| filter.as_object()).cloned().unwrap_or_default();
        SearchTasksService::get_instance()
            .invoke(SearchTasksDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &auth_user_primitives,
                &get_primitives(json!({
                    "userUuid": user_uuid,
                    "taskText": filter.get(TasksKeyEnum::TASK_TEXT),
                    "taskStatus": filter.get(TasksKeyEnum::TASK_STATUS),
                    "taskType": filter.get(TasksKeyEnum::TASK_TYPE),
                    "targetDateFrom": filter.get(TasksKeyEnum::TARGET_DATE_FROM),
                    "targetDateTo": filter.get(TasksKeyEnum::TARGET_DATE_TO),
                    "page": gql_payload.get(TasksKeyEnum::PAGE),
                    "pageSize": gql_payload.get(TasksKeyEnum::PAGE_SIZE),
                })),
            ])))
            .map(|search_tasks_result_dto| search_tasks_result_dto.to_array())
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
