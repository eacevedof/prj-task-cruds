use std::sync::Arc;

use serde_json::Value;

use crate::modules::graph_ql::domain::exceptions::graph_ql_exception::GraphQlException;
use crate::modules::shared::application::primitives::Primitives;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;

/// el resolver de un campo: (HttpRequester de la peticion, gql_payload del campo, objeto padre) -> el array del
/// ResultDto o una GraphQlException. El schema se construye una vez por proceso, asi que la peticion le llega como
/// argumento: el resolver crea el XxxGraphQlController al llamarse (perezoso, como las closures de task-php).
pub type GraphQlResolver = fn(Arc<HttpRequester>, &Primitives, &Value) -> Result<Value, GraphQlException>;

/// campo => resolver, en el orden del modulo.
pub type FieldResolvers = Vec<(&'static str, GraphQlResolver)>;

/// tipo => campo => resolver (el array de task-php, con su orden).
pub type ResolversByType = Vec<(&'static str, FieldResolvers)>;

/// lo que un modulo aporta a /graphql: su parte del schema y quien resuelve cada campo. Cada modulo tiene el suyo en
/// infrastructure/providers y se registra en get_all_modules_providers() de GraphQlSchemaProvidersRegistry.
pub trait InterfaceGraphQlSchemaProvider: Send + Sync {
    fn get_instance() -> Self
    where
        Self: Sized;

    /// SDL del modulo (su <modulo>.graphql): define tipos o extiende los de otro modulo con "extend type".
    fn get_graph_ql_file_content(&self) -> &'static str;

    /// tipo => campo => resolver: devuelve el array del ResultDto o una GraphQlException.
    fn get_controller_resolvers(&self) -> ResolversByType;

    /// el nombre completo del proveedor (el $schemaProviderInstance::class de task-php), para los mensajes del registro.
    fn get_name(&self) -> &'static str {
        std::any::type_name::<Self>()
    }
}
