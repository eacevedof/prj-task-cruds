use async_graphql::dynamic::{FieldValue, ResolverContext};
use async_graphql::{ErrorExtensionValues, Value as GraphqlValue};
use serde_json::Value;

use crate::modules::shared::application::primitives::{Primitives, to_string};
use crate::modules::shared::infrastructure::controllers::graphql_error::GraphqlError;
use crate::modules::tasks::infrastructure::controllers::create_task_graphql_controller::CreateTaskGraphqlController;
use crate::modules::tasks::infrastructure::controllers::delete_task_graphql_controller::DeleteTaskGraphqlController;
use crate::modules::tasks::infrastructure::controllers::get_task_graphql_controller::GetTaskGraphqlController;
use crate::modules::tasks::infrastructure::controllers::search_tasks_graphql_controller::SearchTasksGraphqlController;
use crate::modules::tasks::infrastructure::controllers::update_task_graphql_controller::UpdateTaskGraphqlController;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::infrastructure::controllers::create_user_graphql_controller::CreateUserGraphqlController;
use crate::modules::users::infrastructure::controllers::delete_user_graphql_controller::DeleteUserGraphqlController;
use crate::modules::users::infrastructure::controllers::get_user_graphql_controller::GetUserGraphqlController;
use crate::modules::users::infrastructure::controllers::list_users_graphql_controller::ListUsersGraphqlController;
use crate::modules::users::infrastructure::controllers::log_in_user_graphql_controller::LogInUserGraphqlController;
use crate::modules::users::infrastructure::controllers::regenerate_user_api_token_graphql_controller::RegenerateUserApiTokenGraphqlController;
use crate::modules::users::infrastructure::controllers::update_user_graphql_controller::UpdateUserGraphqlController;

/// los controllers GraphQL de la peticion en curso (el getResolversByType() de task-php): viajan como dato de la
/// peticion porque el schema se construye una sola vez por proceso.
pub struct ResolversByType {
    pub list_users_graphql_controller: ListUsersGraphqlController,
    pub get_user_graphql_controller: GetUserGraphqlController,
    pub create_user_graphql_controller: CreateUserGraphqlController,
    pub log_in_user_graphql_controller: LogInUserGraphqlController,
    pub update_user_graphql_controller: UpdateUserGraphqlController,
    pub delete_user_graphql_controller: DeleteUserGraphqlController,
    pub regenerate_user_api_token_graphql_controller: RegenerateUserApiTokenGraphqlController,
    pub search_tasks_graphql_controller: SearchTasksGraphqlController,
    pub get_task_graphql_controller: GetTaskGraphqlController,
    pub create_task_graphql_controller: CreateTaskGraphqlController,
    pub update_task_graphql_controller: UpdateTaskGraphqlController,
    pub delete_task_graphql_controller: DeleteTaskGraphqlController,
}

/// el resolver de todos los campos de schema.graphql. Los de Query y Mutation (y User.tasks) pasan sus argumentos,
/// como el $args de task-php, al controller GraphQL de su caso de uso; el resto de campos son la clave del mismo nombre
/// del objeto padre (el array que devolvio el controller).
pub fn get_field_value<'a>(
    type_name: &str,
    field_name: &str,
    resolver_context: &ResolverContext<'a>,
) -> async_graphql::Result<Option<FieldValue<'a>>> {
    let resolvers_by_type = resolver_context.data::<ResolversByType>()?;
    let args = get_args(resolver_context);

    let resolved = match (type_name, field_name) {
        ("Query", "users") => resolvers_by_type.list_users_graphql_controller.invoke(),
        ("Query", "user") => resolvers_by_type.get_user_graphql_controller.invoke(&args),
        ("Query", "tasks") => resolvers_by_type.search_tasks_graphql_controller.invoke("", &args),
        ("Query", "task") => resolvers_by_type.get_task_graphql_controller.invoke(&args),
        ("Query", "userTasks") => {
            let user_uuid = to_string(args.get(UsersKeyEnum::USER_UUID).unwrap_or(&Value::Null));
            resolvers_by_type.search_tasks_graphql_controller.invoke(&user_uuid, &args)
        }

        ("Mutation", "createUser") => resolvers_by_type.create_user_graphql_controller.invoke(&args),
        ("Mutation", "logIn") => resolvers_by_type.log_in_user_graphql_controller.invoke(&args),
        ("Mutation", "updateUser") => resolvers_by_type.update_user_graphql_controller.invoke(&args),
        ("Mutation", "deleteUser") => resolvers_by_type.delete_user_graphql_controller.invoke(&args),
        ("Mutation", "regenerateApiToken") => {
            resolvers_by_type.regenerate_user_api_token_graphql_controller.invoke(&args)
        }
        ("Mutation", "createTask") => resolvers_by_type.create_task_graphql_controller.invoke(&args),
        ("Mutation", "updateTask") => resolvers_by_type.update_task_graphql_controller.invoke(&args),
        ("Mutation", "deleteTask") => resolvers_by_type.delete_task_graphql_controller.invoke(&args),

        // User.tasks: las tasks paginadas de ese usuario (el resolver de tipo User de task-php)
        ("User", "tasks") => {
            let user_uuid = to_string(get_parent_value(resolver_context, UsersKeyEnum::USER_UUID));
            resolvers_by_type.search_tasks_graphql_controller.invoke(&user_uuid, &args)
        }

        _ => return Ok(get_field_value_from_json(get_parent_value(resolver_context, field_name).clone())),
    };
    resolved.map(get_field_value_from_json).map_err(get_resolver_error)
}

/// los argumentos del campo (con los defaults del schema ya aplicados) como el $args de task-php.
fn get_args(resolver_context: &ResolverContext<'_>) -> Primitives {
    resolver_context
        .args
        .as_index_map()
        .iter()
        .map(|(name, value)| (name.to_string(), value.clone().into_json().unwrap_or(Value::Null)))
        .collect()
}

/// la clave del objeto padre: el array que devolvio el resolver del campo de arriba.
fn get_parent_value<'a>(resolver_context: &'a ResolverContext<'_>, key: &str) -> &'a Value {
    resolver_context.parent_value.downcast_ref::<Value>().and_then(|parent| parent.get(key)).unwrap_or(&Value::Null)
}

/// lo que devuelve un controller (JSON) como valor de GraphQL: los objetos viajan enteros para que sus campos se
/// resuelvan despues; las listas, elemento a elemento.
fn get_field_value_from_json<'a>(value: Value) -> Option<FieldValue<'a>> {
    match value {
        Value::Null => None,
        Value::Array(items) => Some(FieldValue::list(
            items.into_iter().map(|item| get_field_value_from_json(item).unwrap_or(FieldValue::NULL)),
        )),
        Value::Object(_) => Some(FieldValue::owned_any(value)),
        scalar => Some(FieldValue::value(GraphqlValue::from_json(scalar).unwrap_or(GraphqlValue::Null))),
    }
}

/// GraphqlError -> el error de async-graphql: errors[].message y errors[].extensions {code, errors}.
fn get_resolver_error(graphql_error: GraphqlError) -> async_graphql::Error {
    let mut extensions = ErrorExtensionValues::default();
    if let Some(graphql_extensions) = graphql_error.get_extensions().as_object() {
        for (name, value) in graphql_extensions {
            extensions.set(name, GraphqlValue::from_json(value.clone()).unwrap_or(GraphqlValue::Null));
        }
    }
    let mut error = async_graphql::Error::new(graphql_error.get_message());
    error.extensions = Some(extensions);
    error
}
