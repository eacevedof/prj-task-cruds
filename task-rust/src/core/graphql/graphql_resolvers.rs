use async_graphql::dynamic::{FieldValue, ResolverContext};
use async_graphql::{ErrorExtensionValues, Value as GraphqlValue};
use serde_json::Value;

use crate::modules::shared::application::primitives::{Primitives, to_string};
use crate::modules::shared::infrastructure::controllers::graphql_error::GraphqlError;
use crate::modules::tasks::infrastructure::controllers::tasks_graphql_controller::TasksGraphqlController;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::infrastructure::controllers::users_graphql_controller::UsersGraphqlController;

/// los controllers GraphQL de la peticion en curso (el getResolversByType() de task-php): viajan como dato de la
/// peticion porque el schema se construye una sola vez por proceso.
pub struct ResolversByType {
    pub users_graphql_controller: UsersGraphqlController,
    pub tasks_graphql_controller: TasksGraphqlController,
}

/// el resolver de todos los campos de schema.graphql. Los de Query y Mutation (y User.tasks) pasan sus argumentos,
/// como el $args de task-php, al controller GraphQL de su modulo; el resto de campos son la clave del mismo nombre
/// del objeto padre (el array que devolvio el controller).
pub fn get_field_value<'a>(
    type_name: &str,
    field_name: &str,
    resolver_context: &ResolverContext<'a>,
) -> async_graphql::Result<Option<FieldValue<'a>>> {
    let resolvers_by_type = resolver_context.data::<ResolversByType>()?;
    let users_graphql_controller = &resolvers_by_type.users_graphql_controller;
    let tasks_graphql_controller = &resolvers_by_type.tasks_graphql_controller;
    let args = get_args(resolver_context);

    let resolved = match (type_name, field_name) {
        ("Query", "users") => users_graphql_controller.get_users(),
        ("Query", "user") => users_graphql_controller.get_user(&args),
        ("Query", "tasks") => tasks_graphql_controller.search_tasks("", &args),
        ("Query", "task") => tasks_graphql_controller.get_task(&args),
        ("Query", "userTasks") => {
            let user_uuid = to_string(args.get(UsersKeyEnum::USER_UUID).unwrap_or(&Value::Null));
            tasks_graphql_controller.search_tasks(&user_uuid, &args)
        }

        ("Mutation", "createUser") => users_graphql_controller.create_user(&args),
        ("Mutation", "logIn") => users_graphql_controller.log_in(&args),
        ("Mutation", "updateUser") => users_graphql_controller.update_user(&args),
        ("Mutation", "deleteUser") => users_graphql_controller.delete_user(&args),
        ("Mutation", "regenerateApiToken") => users_graphql_controller.regenerate_api_token(&args),
        ("Mutation", "createTask") => tasks_graphql_controller.create_task(&args),
        ("Mutation", "updateTask") => tasks_graphql_controller.update_task(&args),
        ("Mutation", "deleteTask") => tasks_graphql_controller.delete_task(&args),

        // User.tasks: las tasks paginadas de ese usuario (el resolver de tipo User de task-php)
        ("User", "tasks") => {
            let user_uuid = to_string(get_parent_value(resolver_context, UsersKeyEnum::USER_UUID));
            tasks_graphql_controller.search_tasks(&user_uuid, &args)
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
