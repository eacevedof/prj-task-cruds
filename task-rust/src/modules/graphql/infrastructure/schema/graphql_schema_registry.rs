use crate::core::config::graphql_schema::get_graphql_schema_providers;
use crate::modules::graphql::infrastructure::exceptions::graphql_schema_exception::GraphqlSchemaException;
use crate::modules::graphql::infrastructure::schema::interface_graphql_schema_provider::{
    InterfaceGraphqlSchemaProvider, ResolversByType,
};

/// la cabecera comun del schema (solo comentarios)
const SCHEMA_SDL: &str = include_str!("schema.graphql");

/// une lo que registran los modulos en src/core/config/graphql_schema.rs: la cabecera comun (schema.graphql) mas el
/// SDL de cada proveedor, y la tabla tipo.campo => resolver. Es a /graphql lo que routes.rs al ApiRouter.
pub struct GraphqlSchemaRegistry {
    schema_providers: Vec<Box<dyn InterfaceGraphqlSchemaProvider>>,
}

impl GraphqlSchemaRegistry {
    pub fn get_instance() -> Self {
        Self::new(get_graphql_schema_providers())
    }

    /// con otros proveedores que los de la config (tests).
    pub fn new(schema_providers: Vec<Box<dyn InterfaceGraphqlSchemaProvider>>) -> Self {
        Self { schema_providers }
    }

    /// en el orden del registro: el modulo que define un tipo va antes que los que lo extienden.
    pub fn get_sdl(&self) -> String {
        std::iter::once(SCHEMA_SDL)
            .chain(self.schema_providers.iter().map(|schema_provider| schema_provider.get_sdl()))
            .collect::<Vec<&str>>()
            .join("\n")
    }

    /// Err si dos modulos resuelven el mismo campo (uno pisaria al otro sin avisar).
    pub fn get_resolvers_by_type(&self) -> Result<ResolversByType, GraphqlSchemaException> {
        let mut resolvers_by_type: ResolversByType = Vec::new();
        for schema_provider in &self.schema_providers {
            for (type_name, field_resolvers) in schema_provider.get_resolvers_by_type() {
                let Some((_, registered_field_resolvers)) =
                    resolvers_by_type.iter_mut().find(|(registered_type_name, _)| *registered_type_name == type_name)
                else {
                    resolvers_by_type.push((type_name, field_resolvers));
                    continue;
                };
                let repeated_fields: Vec<&str> = field_resolvers
                    .iter()
                    .map(|(field_name, _)| *field_name)
                    .filter(|field_name| {
                        registered_field_resolvers
                            .iter()
                            .any(|(registered_field_name, _)| registered_field_name == field_name)
                    })
                    .collect();
                if !repeated_fields.is_empty() {
                    return Err(GraphqlSchemaException::fields_already_have_resolver(
                        type_name,
                        &repeated_fields,
                        schema_provider.get_name(),
                    ));
                }
                registered_field_resolvers.extend(field_resolvers);
            }
        }
        Ok(resolvers_by_type)
    }
}
