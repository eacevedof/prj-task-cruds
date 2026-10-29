use std::sync::Arc;

use serde_json::{Value, json};

use crate::modules::shared::application::primitives::{Primitives, get_primitives, merge_primitives};
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::controllers::abstract_graphql_controller::AbstractGraphqlController;
use crate::modules::shared::infrastructure::controllers::graphql_error::GraphqlError;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppError;
use crate::modules::tasks::application::delete_task::delete_task_dto::DeleteTaskDto;
use crate::modules::tasks::application::delete_task::delete_task_service::DeleteTaskService;
use crate::modules::tasks::domain::enums::tasks_key_enum::TasksKeyEnum;
use crate::modules::tasks::domain::exceptions::tasks_exception::TasksException;
use crate::modules::users::domain::exceptions::users_exception::UsersException;

/// mutation deleteTask(task_uuid) (src/core/graphql/schema.graphql). El 401 de la autenticacion llega como
/// UsersException.
pub struct DeleteTaskGraphqlController {
    abstract_graphql_controller: AbstractGraphqlController,
}

extends!(DeleteTaskGraphqlController, abstract_graphql_controller, AbstractGraphqlController);

impl DeleteTaskGraphqlController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_graphql_controller: AbstractGraphqlController::new(http_requester) }
    }

    pub fn invoke(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        let log_title = "DeleteTaskGraphqlController.Invoke";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graphql_error(error, log_title))?;
        DeleteTaskService::get_instance()
            .invoke(DeleteTaskDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &auth_user_primitives,
                &get_primitives(json!({"taskUuid": args.get(TasksKeyEnum::TASK_UUID)})),
            ])))
            .map(|delete_task_result_dto| delete_task_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, log_title))
    }

    /// los catch de task-php: UsersException y TasksException son de dominio; lo demas, inesperado.
    fn get_graphql_error(&self, error: AppError, log_title: &str) -> GraphqlError {
        if let Some(users_exception) = error.downcast_ref::<UsersException>() {
            return self.get_graphql_domain_error(users_exception);
        }
        if let Some(tasks_exception) = error.downcast_ref::<TasksException>() {
            return self.get_graphql_domain_error(tasks_exception);
        }
        self.get_graphql_unexpected_error(&*error, log_title)
    }
}
