use std::sync::Arc;

use serde_json::Value;

use crate::modules::graphql::infrastructure::controllers::graphql_error::GraphqlError;
use crate::modules::shared::application::primitives::Primitives;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;

/// el resolver de un campo: (HttpRequester de la peticion, args del campo, objeto padre) -> el array del ResultDto o un
/// GraphqlError. El schema se construye una vez por proceso, asi que la peticion le llega como argumento: el resolver
/// crea el XxxGraphqlController al llamarse (perezoso, como las closures de task-php).
pub type GraphqlResolver = fn(Arc<HttpRequester>, &Primitives, &Value) -> Result<Value, GraphqlError>;

/// campo => resolver, en el orden del modulo.
pub type FieldResolvers = Vec<(&'static str, GraphqlResolver)>;

/// tipo => campo => resolver (el array de task-php, con su orden).
pub type ResolversByType = Vec<(&'static str, FieldResolvers)>;

/// lo que un modulo aporta a /graphql: su parte del schema y quien resuelve cada campo. Cada modulo tiene el suyo en
/// infrastructure/providers y se registra en src/core/config/graphql_schema.rs.
pub trait InterfaceGraphqlSchemaProvider: Send + Sync {
    fn get_instance() -> Self
    where
        Self: Sized;

    /// SDL del modulo (su <modulo>.graphql): define tipos o extiende los de otro modulo con "extend type".
    fn get_sdl(&self) -> &'static str;

    /// tipo => campo => resolver.
    fn get_resolvers_by_type(&self) -> ResolversByType;

    /// el nombre completo del proveedor (el $schemaProvider::class de task-php), para los mensajes del registro.
    fn get_name(&self) -> &'static str {
        std::any::type_name::<Self>()
    }
}
