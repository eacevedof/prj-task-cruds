use serde_json::Value;

use crate::modules::graph_ql::domain::enums::graph_ql_enum::GraphQlEnum;
use crate::modules::graph_ql::infrastructure::providers::interface_graph_ql_schema_provider::{
    FieldResolvers, InterfaceGraphQlSchemaProvider, ResolversByType,
};
use crate::modules::shared::application::primitives::to_string;
use crate::modules::tasks::infrastructure::controllers::create_task_graph_ql_controller::CreateTaskGraphQlController;
use crate::modules::tasks::infrastructure::controllers::delete_task_graph_ql_controller::DeleteTaskGraphQlController;
use crate::modules::tasks::infrastructure::controllers::get_task_graph_ql_controller::GetTaskGraphQlController;
use crate::modules::tasks::infrastructure::controllers::search_tasks_by_user_graph_ql_controller::SearchTasksByUserGraphQlController;
use crate::modules::tasks::infrastructure::controllers::search_tasks_graph_ql_controller::SearchTasksGraphQlController;
use crate::modules::tasks::infrastructure::controllers::update_task_graph_ql_controller::UpdateTaskGraphQlController;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::infrastructure::providers::users_graph_ql_schema_provider::UsersGraphQlSchemaProvider;

/// parte de tasks en /graphql: tasks.graphql (extiende Query, Mutation y User de users) y sus resolvers, incluido
/// User.tasks, que cuelga de User pero es de tasks. Va despues de UsersGraphQlSchemaProvider en el registro.
pub struct TasksGraphQlSchemaProvider;

impl TasksGraphQlSchemaProvider {
    fn get_query_controllers(&self) -> FieldResolvers {
        vec![
            ("tasks", |http_requester, gql_payload, _parent| {
                SearchTasksGraphQlController::get_instance(http_requester).invoke(gql_payload)
            }),
            ("task", |http_requester, gql_payload, _parent| {
                GetTaskGraphQlController::get_instance(http_requester).invoke(gql_payload)
            }),
        ]
    }

    fn get_mutation_controllers(&self) -> FieldResolvers {
        vec![
            ("createTask", |http_requester, gql_payload, _parent| {
                CreateTaskGraphQlController::get_instance(http_requester).invoke(gql_payload)
            }),
            ("updateTask", |http_requester, gql_payload, _parent| {
                UpdateTaskGraphQlController::get_instance(http_requester).invoke(gql_payload)
            }),
            ("deleteTask", |http_requester, gql_payload, _parent| {
                DeleteTaskGraphQlController::get_instance(http_requester).invoke(gql_payload)
            }),
        ]
    }
}

impl InterfaceGraphQlSchemaProvider for TasksGraphQlSchemaProvider {
    fn get_instance() -> Self {
        Self
    }

    fn get_graph_ql_file_content(&self) -> &'static str {
        include_str!("tasks.graphql")
    }

    fn get_controller_resolvers(&self) -> ResolversByType {
        vec![
            (GraphQlEnum::TYPE_QUERY, self.get_query_controllers()),
            (GraphQlEnum::TYPE_MUTATION, self.get_mutation_controllers()),
            // las tareas de un usuario.
            // User.tasks: solo ejemplo de extender un tipo de otro modulo, no lo consume ningun front (ver tasks.graphql)
            (
                UsersGraphQlSchemaProvider::TYPE_USER,
                vec![("tasks", |http_requester, gql_payload, user| {
                    SearchTasksByUserGraphQlController::get_instance(http_requester)
                        .invoke(&to_string(user.get(UsersKeyEnum::USER_UUID).unwrap_or(&Value::Null)), gql_payload)
                })],
            ),
        ]
    }
}
