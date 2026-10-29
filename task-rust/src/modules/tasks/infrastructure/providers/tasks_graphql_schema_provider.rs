use serde_json::Value;

use crate::modules::graphql::domain::enums::graphql_enum::GraphqlEnum;
use crate::modules::graphql::infrastructure::schema::interface_graphql_schema_provider::{
    InterfaceGraphqlSchemaProvider, ResolversByType,
};
use crate::modules::shared::application::primitives::to_string;
use crate::modules::tasks::domain::enums::tasks_key_enum::TasksKeyEnum;
use crate::modules::tasks::infrastructure::controllers::create_task_graphql_controller::CreateTaskGraphqlController;
use crate::modules::tasks::infrastructure::controllers::delete_task_graphql_controller::DeleteTaskGraphqlController;
use crate::modules::tasks::infrastructure::controllers::get_task_graphql_controller::GetTaskGraphqlController;
use crate::modules::tasks::infrastructure::controllers::search_tasks_by_user_graphql_controller::SearchTasksByUserGraphqlController;
use crate::modules::tasks::infrastructure::controllers::search_tasks_graphql_controller::SearchTasksGraphqlController;
use crate::modules::tasks::infrastructure::controllers::update_task_graphql_controller::UpdateTaskGraphqlController;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;

/// parte de tasks en /graphql: tasks.graphql (extiende Query, Mutation y User de users) y sus resolvers, incluido
/// User.tasks, que cuelga de User pero es de tasks. Va despues de UsersGraphqlSchemaProvider en el registro.
pub struct TasksGraphqlSchemaProvider;

impl InterfaceGraphqlSchemaProvider for TasksGraphqlSchemaProvider {
    fn get_instance() -> Self {
        Self
    }

    fn get_sdl(&self) -> &'static str {
        include_str!("tasks.graphql")
    }

    fn get_resolvers_by_type(&self) -> ResolversByType {
        vec![
            (
                GraphqlEnum::TYPE_QUERY,
                vec![
                    ("tasks", |http_requester, args, _parent| {
                        SearchTasksGraphqlController::get_instance(http_requester).invoke(args)
                    }),
                    ("task", |http_requester, args, _parent| {
                        GetTaskGraphqlController::get_instance(http_requester).invoke(args)
                    }),
                    ("userTasks", |http_requester, args, _parent| {
                        SearchTasksByUserGraphqlController::get_instance(http_requester)
                            .invoke(&to_string(args.get(TasksKeyEnum::USER_UUID).unwrap_or(&Value::Null)), args)
                    }),
                ],
            ),
            (
                GraphqlEnum::TYPE_MUTATION,
                vec![
                    ("createTask", |http_requester, args, _parent| {
                        CreateTaskGraphqlController::get_instance(http_requester).invoke(args)
                    }),
                    ("updateTask", |http_requester, args, _parent| {
                        UpdateTaskGraphqlController::get_instance(http_requester).invoke(args)
                    }),
                    ("deleteTask", |http_requester, args, _parent| {
                        DeleteTaskGraphqlController::get_instance(http_requester).invoke(args)
                    }),
                ],
            ),
            (
                GraphqlEnum::TYPE_USER,
                vec![("tasks", |http_requester, args, user| {
                    SearchTasksByUserGraphqlController::get_instance(http_requester)
                        .invoke(&to_string(user.get(UsersKeyEnum::USER_UUID).unwrap_or(&Value::Null)), args)
                })],
            ),
        ]
    }
}
