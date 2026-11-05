use std::error::Error;
use std::fmt;

use crate::core::config::tr;
use crate::modules::graph_ql::domain::enums::graph_ql_message_enum::GraphQlMessageEnum;
use crate::modules::shared::domain::enums::exception_enum::ExceptionEnum;
use crate::modules::shared::infrastructure::exceptions::interface_exception::InterfaceException;

/// el registro de /graphql esta mal montado: es un fallo del programador, no de la peticion. Salta al montar el
/// schema, que en Rust se monta una vez por proceso (con la primera peticion a /graphql) y se guarda tal cual, asi que
/// rompe TODAS las llamadas a /graphql (aunque la query no toque el campo afectado). La recoge
/// ExecuteGraphQlQueryController: el mensaje va al log y el cliente solo recibe un 500 con el texto generico, sin data.
/// Las factorias devuelven la excepcion (el never de task-php): quien la lanza hace return Err(...).
#[derive(Debug, Clone)]
pub struct GraphQlSchemaException {
    message: String,
    code: i64,
}

impl GraphQlSchemaException {
    /// dos providers registran resolver para el mismo tipo.campo: el segundo pisaria al primero sin avisar.
    /// La devuelve GraphQlSchemaProvidersRegistry::get_all_merged_resolvers_by_type() al unir los providers en el orden
    /// de get_all_modules_providers().
    ///
    /// Ejemplo: TasksGraphQlSchemaProvider anade por error "user" en Query, que ya es de Users
    ///   type_name            "Query"
    ///   field_names          ["user"]  (o varios: ["tasks", "task"] -> "Query.tasks, task")
    ///   schema_provider_name "task_rust::modules::tasks::infrastructure::providers::...::TasksGraphQlSchemaProvider"
    ///                        (el que llega SEGUNDO y choca, no el que ya tenia el campo)
    ///   mensaje: graphql: Query.user already has a resolver (task_rust::...::TasksGraphQlSchemaProvider)
    ///
    /// type_name: tipo del schema: "Query", "Mutation" o uno de negocio como "User"
    /// field_names: campos repetidos de ese tipo
    /// schema_provider_name: nombre completo del provider que intenta registrarlos de nuevo
    pub fn fields_already_have_resolver(type_name: &str, field_names: &[&str], schema_provider_name: &str) -> Self {
        Self {
            message: tr(
                GraphQlMessageEnum::GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X,
                &[&type_name, &field_names.join(", "), &schema_provider_name],
            ),
            code: ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
        }
    }

    /// un campo de Query o Mutation esta en el .graphql pero ningun provider le da resolver: sin esto devolveria null
    /// sin avisar. La devuelve fail_if_root_field_without_resolver() de ExecuteGraphQlQueryService al montar el schema.
    /// Solo se comprueban los tipos raiz: un campo de un tipo de negocio sin resolver (p. ej. User.tasks) no salta
    /// aqui, lo resuelve el resolver por defecto leyendo del objeto padre (y daria null).
    ///
    /// Ejemplo: se anade "archiveTask(task_uuid: ID!): Task" al "extend type Mutation" de tasks.graphql y se olvida
    /// el resolver en get_mutation_controllers() de TasksGraphQlSchemaProvider
    ///   type_name   "Mutation"
    ///   field_names ["archiveTask"]
    ///   mensaje: graphql: Mutation.archiveTask has no resolver
    ///
    /// type_name: solo "Query" o "Mutation" (GraphQlEnum::TYPE_QUERY / TYPE_MUTATION)
    /// field_names: campos declarados en el schema sin resolver registrado
    pub fn fields_without_resolver(type_name: &str, field_names: &[&str]) -> Self {
        Self {
            message: tr(
                GraphQlMessageEnum::GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER,
                &[&type_name, &field_names.join(", ")],
            ),
            code: ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
        }
    }
}

impl InterfaceException for GraphQlSchemaException {
    fn get_code(&self) -> i64 {
        self.code
    }
}

impl fmt::Display for GraphQlSchemaException {
    fn fmt(&self, formatter: &mut fmt::Formatter<'_>) -> fmt::Result {
        formatter.write_str(&self.message)
    }
}

impl Error for GraphQlSchemaException {}
