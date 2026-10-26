use std::sync::Arc;

use serde_json::{Value, json};

use crate::modules::shared::application::primitives::{Primitives, get_primitives, merge_primitives};
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use crate::modules::shared::infrastructure::controllers::abstract_graphql_controller::AbstractGraphqlController;
use crate::modules::shared::infrastructure::controllers::graphql_error::GraphqlError;
use crate::modules::shared::infrastructure::exceptions::fields_exception::FieldsException;
use crate::modules::shared::infrastructure::exceptions::interface_exception::AppError;
use crate::modules::tasks::application::create_task::create_task_dto::CreateTaskDto;
use crate::modules::tasks::application::create_task::create_task_service::CreateTaskService;
use crate::modules::tasks::application::delete_task::delete_task_dto::DeleteTaskDto;
use crate::modules::tasks::application::delete_task::delete_task_service::DeleteTaskService;
use crate::modules::tasks::application::get_task::get_task_dto::GetTaskDto;
use crate::modules::tasks::application::get_task::get_task_service::GetTaskService;
use crate::modules::tasks::application::search_tasks::search_tasks_dto::SearchTasksDto;
use crate::modules::tasks::application::search_tasks::search_tasks_service::SearchTasksService;
use crate::modules::tasks::application::update_task::update_task_dto::UpdateTaskDto;
use crate::modules::tasks::application::update_task::update_task_service::UpdateTaskService;
use crate::modules::tasks::domain::enums::tasks_key_enum::TasksKeyEnum;
use crate::modules::tasks::domain::exceptions::tasks_exception::TasksException;
use crate::modules::users::domain::exceptions::users_exception::UsersException;

/// resolvers GraphQL de tasks (src/core/graphql/schema.graphql). El 401 de la autenticacion llega como
/// UsersException.
pub struct TasksGraphqlController {
    abstract_graphql_controller: AbstractGraphqlController,
}

extends!(TasksGraphqlController, abstract_graphql_controller, AbstractGraphqlController);

impl TasksGraphqlController {
    pub fn get_instance(http_requester: Arc<HttpRequester>) -> Self {
        Self { abstract_graphql_controller: AbstractGraphqlController::new(http_requester) }
    }

    /// query tasks (user_uuid "") y userTasks / User.tasks: busqueda paginada con filtros.
    /// args {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
    pub fn search_tasks(&self, user_uuid: &str, args: &Primitives) -> Result<Value, GraphqlError> {
        let log_title = "TasksGraphqlController.SearchTasks";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graphql_error(error, log_title))?;
        let filter = args.get(TasksKeyEnum::FILTER).and_then(|filter| filter.as_object()).cloned().unwrap_or_default();
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
                    "page": args.get(TasksKeyEnum::PAGE),
                    "pageSize": args.get(TasksKeyEnum::PAGE_SIZE),
                })),
            ])))
            .map(|search_tasks_result_dto| search_tasks_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, log_title))
    }

    pub fn get_task(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        let log_title = "TasksGraphqlController.GetTask";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graphql_error(error, log_title))?;
        GetTaskService::get_instance()
            .invoke(GetTaskDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &auth_user_primitives,
                &get_primitives(json!({"taskUuid": args.get(TasksKeyEnum::TASK_UUID)})),
            ])))
            .map(|get_task_result_dto| get_task_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, log_title))
    }

    pub fn create_task(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        let log_title = "TasksGraphqlController.CreateTask";
        let auth_user_primitives =
            self.get_auth_user_or_fail().map_err(|error| self.get_graphql_error(error, log_title))?;
        CreateTaskService::get_instance()
            .invoke(CreateTaskDto::from_primitives(&merge_primitives(&[
                &self.get_http_request().get_metadata(),
                &auth_user_primitives,
                &get_primitives(json!({
                    "taskTitle": args.get(TasksKeyEnum::TASK_TITLE),
                    "taskStatus": args.get(TasksKeyEnum::TASK_STATUS),
                    "taskType": args.get(TasksKeyEnum::TASK_TYPE),
                    "targetDate": args.get(TasksKeyEnum::TARGET_DATE),
                    "taskDescription": args.get(TasksKeyEnum::TASK_DESCRIPTION),
                })),
            ])))
            .map(|create_task_result_dto| create_task_result_dto.to_array())
            .map_err(|error| self.get_graphql_error(error, log_title))
    }

    pub fn update_task(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        let log_title = "TasksGraphqlController.UpdateTask";
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

    pub fn delete_task(&self, args: &Primitives) -> Result<Value, GraphqlError> {
        let log_title = "TasksGraphqlController.DeleteTask";
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
