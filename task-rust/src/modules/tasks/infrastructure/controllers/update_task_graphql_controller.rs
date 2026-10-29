use std::sync::Arc;

use serde_json::{Value, json};

use crate::modules::shared::application::primitives::{Primitives, get_primitives, merge_primitives};
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::controllers::abstract_graphql_controller::AbstractGraphqlController;
use crate::modules::shared::infrastructure::controllers::graphql_error::GraphqlError;
use crate::modules::shared::infrastructure::exceptions::fields_exception::FieldsException;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppError;
use crate::modules::tasks::application::update_task::update_task_dto::UpdateTaskDto;
use crate::modules::tasks::application::update_task::update_task_service::UpdateTaskService;
use crate::modules::tasks::domain::enums::tasks_key_enum::TasksKeyEnum;
use crate::modules::tasks::domain::exceptions::tasks_exception::TasksException;
use crate::modules::users::domain::exceptions::users_exception::UsersException;

/// mutation updateTask (src/core/graphql/schema.graphql). El 401 de la autenticacion llega como UsersException.
pub struct UpdateTaskGraphqlController {
    abstract_graphql_controller: AbstractGraphqlController,
}

extends!(UpdateTaskGraphqlController, abstract_graphql_controller, AbstractGraphqlController);

impl UpdateTaskGraphqlController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_graphql_controller: AbstractGraphqlController::new(http_requester) }
    }

    pub fn invoke(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        let log_title = "UpdateTaskGraphqlController.Invoke";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graphql_error(error, log_title))?;
        UpdateTaskService::get_instance()
            .invoke(UpdateTaskDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &auth_user_primitives,
                &get_primitives(json!({
                    "taskUuid": args.get(TasksKeyEnum::TASK_UUID),
                    "taskTitle": args.get(TasksKeyEnum::TASK_TITLE),
                    "taskStatus": args.get(TasksKeyEnum::TASK_STATUS),
                    "taskType": args.get(TasksKeyEnum::TASK_TYPE),
                    "targetDate": args.get(TasksKeyEnum::TARGET_DATE),
                    "taskDescription": args.get(TasksKeyEnum::TASK_DESCRIPTION),
                })),
            ])))
            .map(|update_task_result_dto| update_task_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, log_title))
    }

    /// los catch de task-php: FieldsException, UsersException y TasksException son de dominio; lo demas, inesperado.
    fn get_graphql_error(&self, error: AppError, log_title: &str) -> GraphqlError {
        if let Some(fields_exception) = error.downcast_ref::<FieldsException>() {
            return self.get_graphql_domain_error(fields_exception);
        }
        if let Some(users_exception) = error.downcast_ref::<UsersException>() {
            return self.get_graphql_domain_error(users_exception);
        }
        if let Some(tasks_exception) = error.downcast_ref::<TasksException>() {
            return self.get_graphql_domain_error(tasks_exception);
        }
        self.get_graphql_unexpected_error(&*error, log_title)
    }
}
