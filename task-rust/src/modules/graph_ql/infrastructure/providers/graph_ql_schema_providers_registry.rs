use crate::modules::graph_ql::infrastructure::exceptions::graph_ql_schema_exception::GraphQlSchemaException;
use crate::modules::graph_ql::infrastructure::providers::interface_graph_ql_schema_provider::{
    InterfaceGraphQlSchemaProvider, ResolversByType,
};
use crate::modules::tasks::infrastructure::providers::tasks_graph_ql_schema_provider::TasksGraphQlSchemaProvider;
use crate::modules::users::infrastructure::providers::users_graph_ql_schema_provider::UsersGraphQlSchemaProvider;

/// la cabecera comun del schema (solo comentarios)
const SCHEMA_GRAPHQL: &str = include_str!("schema.graphql");

/// une lo que registran los modulos en get_all_modules_providers(): la cabecera comun (schema.graphql) mas el SDL de
/// cada proveedor, y la tabla tipo.campo => resolver. Es a /graphql lo que routes.rs al ApiRouter.
pub struct GraphQlSchemaProvidersRegistry {
    schema_providers_instances: Vec<Box<dyn InterfaceGraphQlSchemaProvider>>,
}

impl GraphQlSchemaProvidersRegistry {
    pub fn get_instance() -> Self {
        Self { schema_providers_instances: Self::get_all_modules_providers() }
    }

    /// en el orden del registro: el modulo que define un tipo va antes que los que lo extienden.
    pub fn get_graph_ql_schema_as_string(&self) -> String {
        std::iter::once(SCHEMA_GRAPHQL)
            .chain(
                self.schema_providers_instances
                    .iter()
                    .map(|schema_provider_instance| schema_provider_instance.get_graph_ql_file_content()),
            )
            .collect::<Vec<&str>>()
            .join("\n")
    }

    /// une los get_controller_resolvers() de cada provider en una sola tabla tipo => campo => resolver. Los tipos y
    /// campos salen en el orden del registro: primero los de Users y detras los que anade Tasks. Cada resolver es una
    /// fn(http_requester, gql_payload, parent) que crea su controller solo si la consulta pide ese campo.
    ///
    /// Con el registro actual (Users, Tasks) devuelve:
    /// [
    ///     ("Query", [
    ///         ("users", ListUsersGraphQlController...),                    // Users
    ///         ("user", GetUserGraphQlController...),                       // Users
    ///         ("tasks", SearchTasksGraphQlController...),                  // Tasks
    ///         ("task", GetTaskGraphQlController...),                       // Tasks
    ///     ]),
    ///     ("Mutation", [
    ///         ("createUser", ...), ("logIn", ...), ("updateUser", ...), ("deleteUser", ...),
    ///         ("regenerateApiToken", ...),                                 // Users
    ///         ("createTask", ...), ("updateTask", ...), ("deleteTask", ...), // Tasks
    ///     ]),
    ///     ("User", [
    ///         ("tasks", SearchTasksByUserGraphQlController...),            // Tasks
    ///     ]),
    /// ]
    /// "Query.tasks" y "User.tasks" no chocan: la clave de choque es tipo + campo, no solo el campo.
    ///
    /// Err (GraphQlSchemaException) si dos modulos resuelven el mismo campo (uno pisaria al otro sin avisar).
    pub fn get_all_merged_resolvers_by_type(&self) -> Result<ResolversByType, GraphQlSchemaException> {
        let mut resolvers_by_type: ResolversByType = Vec::new();
        for schema_provider_instance in &self.schema_providers_instances {
            for (type_name, controller_resolvers) in schema_provider_instance.get_controller_resolvers() {
                let Some((_, registered_controller_resolvers)) =
                    resolvers_by_type.iter_mut().find(|(registered_type_name, _)| *registered_type_name == type_name)
                else {
                    resolvers_by_type.push((type_name, controller_resolvers));
                    continue;
                };
                let repeated_fields: Vec<&str> = controller_resolvers
                    .iter()
                    .map(|(field_name, _)| *field_name)
                    .filter(|field_name| {
                        registered_controller_resolvers
                            .iter()
                            .any(|(registered_field_name, _)| registered_field_name == field_name)
                    })
                    .collect();
                if !repeated_fields.is_empty() {
                    return Err(GraphQlSchemaException::fields_already_have_resolver(
                        type_name,
                        &repeated_fields,
                        schema_provider_instance.get_name(),
                    ));
                }
                registered_controller_resolvers.extend(controller_resolvers);
            }
        }
        Ok(resolvers_by_type)
    }

    /// el orden importa: primero los que definen un tipo, despues los que lo extienden.
    /// Cada modulo tiene su propio provider
    fn get_all_modules_providers() -> Vec<Box<dyn InterfaceGraphQlSchemaProvider>> {
        vec![Box::new(UsersGraphQlSchemaProvider::get_instance()), Box::new(TasksGraphQlSchemaProvider::get_instance())]
    }
}
