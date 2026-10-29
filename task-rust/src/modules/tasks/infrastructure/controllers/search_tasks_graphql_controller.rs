use std::sync::Arc;

use serde_json::{Value, json};

use crate::modules::graphql::infrastructure::controllers::abstract_graphql_controller::AbstractGraphqlController;
use crate::modules::graphql::infrastructure::controllers::graphql_error::GraphqlError;
use crate::modules::shared::application::primitives::{Primitives, get_primitives, merge_primitives};
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::exceptions::fields_exception::FieldsException;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppError;
use crate::modules::tasks::application::search_tasks::search_tasks_dto::SearchTasksDto;
use crate::modules::tasks::application::search_tasks::search_tasks_service::SearchTasksService;
use crate::modules::tasks::domain::enums::tasks_key_enum::TasksKeyEnum;
use crate::modules::tasks::domain::exceptions::tasks_exception::TasksException;
use crate::modules::users::domain::exceptions::users_exception::UsersException;

/// query tasks (src/modules/tasks/infrastructure/providers/tasks.graphql): las tasks del usuario autenticado, paginadas y
/// con filtros. Las de un usuario concreto (userTasks, User.tasks) van por SearchTasksByUserGraphqlController.
/// El 401 de la autenticacion llega como UsersException.
pub struct SearchTasksGraphqlController {
    abstract_graphql_controller: AbstractGraphqlController,
}

extends!(SearchTasksGraphqlController, abstract_graphql_controller, AbstractGraphqlController);

impl SearchTasksGraphqlController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_graphql_controller: AbstractGraphqlController::new(http_requester) }
    }

    /// args {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
    pub fn invoke(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        let log_title = "SearchTasksGraphqlController.Invoke";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graphql_error(error, log_title))?;
        let filter = args.get(TasksKeyEnum::FILTER).and_then(|filter| filter.as_object()).cloned().unwrap_or_default();
        SearchTasksService::get_instance()
            .invoke(SearchTasksDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &auth_user_primitives,
                &get_primitives(json!({
                    "taskText": filter.get(TasksKeyEnum::TASK_TEXT),
                    "taskStatus": filter.get(TasksKeyEnum::TASK_STATUS),
                    "taskType": filter.get(TasksKeyEnum::TASK_TYPE),
                    "targetDateFrom": filter.get(TasksKeyEnum::TARGET_DATE_FROM),
                    "targetDateTo": filter.get(TasksKeyEnum::TARGET_DATE_TO),
                    "page": args.get(TasksKeyEnum::PAGE),
                    "pageSize": args.get(TasksKeyEnum::PAGE_SIZE),
                })),
            ])))
            .map(|search_tasks_result_dto| search_tasks_result_dto.to_array())
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
