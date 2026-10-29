use crate::modules::graphql::infrastructure::schema::interface_graphql_schema_provider::InterfaceGraphqlSchemaProvider;
use crate::modules::tasks::infrastructure::providers::tasks_graphql_schema_provider::TasksGraphqlSchemaProvider;
use crate::modules::users::infrastructure::providers::users_graphql_schema_provider::UsersGraphqlSchemaProvider;

/// registro de los modulos en /graphql (lo lee GraphqlSchemaRegistry solo cuando llega una peticion a /graphql).
/// Cada modulo nuevo anade aqui su XxxGraphqlSchemaProvider, despues de los modulos cuyos tipos extiende.
pub fn get_graphql_schema_providers() -> Vec<Box<dyn InterfaceGraphqlSchemaProvider>> {
    vec![Box::new(UsersGraphqlSchemaProvider::get_instance()), Box::new(TasksGraphqlSchemaProvider::get_instance())]
}
