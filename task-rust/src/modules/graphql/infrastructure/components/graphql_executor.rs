use std::sync::{Arc, OnceLock};

use async_graphql::dynamic::{
    Field, FieldFuture, FieldValue, InputObject, InputValue, Object, ResolverContext, Schema, TypeRef,
};
use async_graphql::parser::parse_schema;
use async_graphql::parser::types::{
    BaseType, InputValueDefinition, Type, TypeDefinition, TypeKind, TypeSystemDefinition,
};
use async_graphql::{ErrorExtensionValues, PathSegment, Request, ServerError, Value as GraphqlValue, Variables};
use serde_json::Value;

use crate::modules::graphql::domain::enums::graphql_enum::GraphqlEnum;
use crate::modules::graphql::infrastructure::controllers::graphql_error::GraphqlError;
use crate::modules::graphql::infrastructure::dtos::graphql_request_dto::GraphqlRequestDto;
use crate::modules::graphql::infrastructure::dtos::graphql_result_dto::GraphqlResultDto;
use crate::modules::graphql::infrastructure::exceptions::graphql_schema_exception::GraphqlSchemaException;
use crate::modules::graphql::infrastructure::schema::graphql_schema_registry::GraphqlSchemaRegistry;
use crate::modules::graphql::infrastructure::schema::interface_graphql_schema_provider::{
    GraphqlResolver, ResolversByType,
};
use crate::modules::shared::application::primitives::Primitives;
use crate::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;

/// el de la config (src/core/config/graphql_schema.rs): se monta con la primera peticion a /graphql y vale para todo el
/// proceso (async-graphql construye el schema en codigo; montarlo en cada peticion seria rehacer el trabajo).
static GRAPHQL_EXECUTOR: OnceLock<GraphqlExecutor> = OnceLock::new();

/// monta el schema con lo que registran los modulos y ejecuta la peticion con async-graphql: el unico sitio (junto a
/// AbstractGraphqlController y su GraphqlError) que conoce la libreria. Componente: no captura ni loguea.
pub struct GraphqlExecutor {
    /// el registro mal montado se guarda como error: cada peticion lo devuelve (500) en vez de reintentar
    schema: Result<Schema, GraphqlSchemaException>,
}

impl GraphqlExecutor {
    pub fn get_instance() -> &'static Self {
        GRAPHQL_EXECUTOR.get_or_init(|| Self::new(&GraphqlSchemaRegistry::get_instance()))
    }

    /// con otro registro que el de la config (tests).
    pub fn new(graphql_schema_registry: &GraphqlSchemaRegistry) -> Self {
        Self { schema: get_schema(graphql_schema_registry) }
    }

    /// los errores de GraphQL (sintaxis, validacion, dominio de un resolver) van en el resultado, no son Err.
    /// Err (GraphqlSchemaException) si el registro esta mal montado (campo repetido o sin resolver).
    pub fn execute(
        &self,
        graphql_request_dto: &GraphqlRequestDto,
        http_requester: Arc<HttpRequester>,
    ) -> Result<GraphqlResultDto, GraphqlSchemaException> {
        let schema = self.schema.as_ref().map_err(Clone::clone)?;
        let mut request = Request::new(graphql_request_dto.get_query())
            .variables(Variables::from_json(Value::Object(graphql_request_dto.get_variables().clone())))
            .data(http_requester);
        if let Some(operation_name) = graphql_request_dto.get_operation_name() {
            request = request.operation_name(operation_name);
        }
        let response = pollster::block_on(schema.execute(request));
        Ok(GraphqlResultDto::from_primitives(&get_graphql_result(response)))
    }
}

/// el BuildSchema::build() de webonyx con su decorador de resolvers: async-graphql construye los schemas en codigo,
/// asi que aqui se lee el SDL y se da de alta cada tipo, campo y argumento (con su descripcion y su default). Cada
/// campo con resolver en el registro lo usa; el resto sale de la clave del mismo nombre del objeto padre.
/// Un SDL que no compila es un fallo de programacion: panic (el controller responde 500).
fn get_schema(graphql_schema_registry: &GraphqlSchemaRegistry) -> Result<Schema, GraphqlSchemaException> {
    let resolvers_by_type = graphql_schema_registry.get_resolvers_by_type()?;
    let type_definitions =
        get_sdl_type_definitions(&graphql_schema_registry.get_sdl()).unwrap_or_else(|error| panic!("{error}"));
    fail_if_root_field_without_resolver(&type_definitions, &resolvers_by_type)?;

    let mut schema_builder = Schema::build(GraphqlEnum::TYPE_QUERY, Some(GraphqlEnum::TYPE_MUTATION), None)
        .limit_depth(GraphqlEnum::MAX_QUERY_DEPTH);
    for type_definition in type_definitions {
        let type_name = type_definition.name.node.to_string();
        let type_description = type_definition.description.map(|description| description.node);

        match type_definition.kind {
            TypeKind::Object(object_type) => {
                let mut object = Object::new(&type_name);
                if let Some(type_description) = type_description {
                    object = object.description(type_description);
                }
                for field_definition in object_type.fields {
                    let field_definition = field_definition.node;
                    let field_name = field_definition.name.node.to_string();
                    let resolver = get_resolver(&resolvers_by_type, &type_name, &field_name);
                    let resolver_field_name = field_name.clone();
                    let is_nullable = field_definition.ty.node.nullable;
                    let mut field = Field::new(
                        field_name,
                        get_type_ref(&field_definition.ty.node),
                        move |resolver_context: ResolverContext<'_>| {
                            let field_value = match get_field_value(resolver, &resolver_field_name, &resolver_context) {
                                // como webonyx: el error va a errors[] con su path y el campo queda a null (el resto
                                // de la consulta sigue). Un campo no nulo no puede: su error corta la consulta
                                Err(error) if is_nullable => {
                                    resolver_context.add_error(get_field_server_error(error, &resolver_context));
                                    Ok(None)
                                }
                                field_value => field_value,
                            };
                            FieldFuture::new(async move { field_value })
                        },
                    );
                    if let Some(field_description) = field_definition.description {
                        field = field.description(field_description.node);
                    }
                    for argument in field_definition.arguments {
                        field = field.argument(get_input_value(argument.node));
                    }
                    object = object.field(field);
                }
                schema_builder = schema_builder.register(object);
            }
            TypeKind::InputObject(input_object_type) => {
                let mut input_object = InputObject::new(&type_name);
                if let Some(type_description) = type_description {
                    input_object = input_object.description(type_description);
                }
                for input_value_definition in input_object_type.fields {
                    input_object = input_object.field(get_input_value(input_value_definition.node));
                }
                schema_builder = schema_builder.register(input_object);
            }
            // los .graphql solo definen objetos e inputs: los escalares son los de GraphQL (String, Int, ID)
            _ => {}
        }
    }
    Ok(schema_builder.finish().unwrap_or_else(|error| panic!("schema graphql: {error}")))
}

/// un campo de Query o Mutation sin resolver devolveria null sin avisar: es un fallo del registro.
fn fail_if_root_field_without_resolver(
    type_definitions: &[TypeDefinition],
    resolvers_by_type: &ResolversByType,
) -> Result<(), GraphqlSchemaException> {
    for root_type_name in [GraphqlEnum::TYPE_QUERY, GraphqlEnum::TYPE_MUTATION] {
        let Some(TypeKind::Object(root_object_type)) = type_definitions
            .iter()
            .find(|type_definition| type_definition.name.node == root_type_name)
            .map(|type_definition| &type_definition.kind)
        else {
            continue;
        };
        let fields_without_resolver: Vec<&str> = root_object_type
            .fields
            .iter()
            .map(|field_definition| field_definition.node.name.node.as_str())
            .filter(|field_name| get_resolver(resolvers_by_type, root_type_name, field_name).is_none())
            .collect();
        if !fields_without_resolver.is_empty() {
            return Err(GraphqlSchemaException::fields_without_resolver(root_type_name, &fields_without_resolver));
        }
    }
    Ok(())
}

fn get_resolver(resolvers_by_type: &ResolversByType, type_name: &str, field_name: &str) -> Option<GraphqlResolver> {
    resolvers_by_type
        .iter()
        .find(|(resolver_type_name, _)| *resolver_type_name == type_name)
        .and_then(|(_, field_resolvers)| {
            field_resolvers.iter().find(|(resolver_field_name, _)| *resolver_field_name == field_name)
        })
        .map(|(_, resolver)| *resolver)
}

/// el resolveField del decorador de task-php: el resolver del registro con los argumentos del campo y el objeto padre,
/// o el resolver por defecto (la clave del mismo nombre del array del ResultDto padre).
fn get_field_value<'a>(
    resolver: Option<GraphqlResolver>,
    field_name: &str,
    resolver_context: &ResolverContext<'a>,
) -> async_graphql::Result<Option<FieldValue<'a>>> {
    let parent_value = resolver_context.parent_value.downcast_ref::<Value>().unwrap_or(&Value::Null);
    let Some(resolver) = resolver else {
        return Ok(get_field_value_from_json(parent_value.get(field_name).cloned().unwrap_or(Value::Null)));
    };
    let http_requester = resolver_context.data::<Arc<HttpRequester>>()?;
    resolver(Arc::clone(http_requester), &get_args(resolver_context), parent_value)
        .map(get_field_value_from_json)
        .map_err(get_resolver_error)
}

/// las definiciones de tipo del SDL con cada "extend type" fusionado en su tipo base (tasks.graphql extiende Query,
/// Mutation y User de users.graphql). webonyx entiende las extensiones; el Schema dinamico de async-graphql no, asi
/// que se fusionan aqui sin tocar los .graphql: los campos de la extension van detras de los del tipo base.
fn get_sdl_type_definitions(sdl: &str) -> Result<Vec<TypeDefinition>, String> {
    let service_document = parse_schema(sdl).map_err(|error| format!("schema graphql: {error}"))?;
    let mut type_definitions: Vec<TypeDefinition> = Vec::new();
    for type_system_definition in service_document.definitions {
        let TypeSystemDefinition::Type(type_definition) = type_system_definition else {
            continue;
        };
        let type_definition = type_definition.node;
        if !type_definition.extend {
            type_definitions.push(type_definition);
            continue;
        }
        let type_name = type_definition.name.node.to_string();
        let Some(base_type_definition) =
            type_definitions.iter_mut().find(|base_type_definition| base_type_definition.name.node == type_name)
        else {
            return Err(format!("schema graphql: extend type {type_name} sin su tipo base delante"));
        };
        match (&mut base_type_definition.kind, type_definition.kind) {
            (TypeKind::Object(base_object_type), TypeKind::Object(object_type)) => {
                base_object_type.fields.extend(object_type.fields);
            }
            (TypeKind::InputObject(base_input_object_type), TypeKind::InputObject(input_object_type)) => {
                base_input_object_type.fields.extend(input_object_type.fields);
            }
            _ => return Err(format!("schema graphql: extend type {type_name} no es del mismo tipo que su base")),
        }
    }
    Ok(type_definitions)
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

/// el error de un resolver con la posicion del campo en la query (locations) y su ruta en la respuesta (path).
fn get_field_server_error(error: async_graphql::Error, resolver_context: &ResolverContext<'_>) -> ServerError {
    let mut server_error = error.into_server_error(resolver_context.item.pos);
    let path = resolver_context.path_node.as_ref().and_then(|path_node| serde_json::to_value(path_node).ok());
    server_error.path = path
        .as_ref()
        .and_then(|path| path.as_array())
        .map(|segments| {
            segments
                .iter()
                .map(|segment| match segment.as_u64() {
                    Some(index) => PathSegment::Index(index as usize),
                    None => PathSegment::Field(segment.as_str().unwrap_or("").to_string()),
                })
                .collect()
        })
        .unwrap_or_default();
    server_error
}

/// un argumento de campo o un campo de input, con su default ("page: Int = 1") y su descripcion.
fn get_input_value(input_value_definition: InputValueDefinition) -> InputValue {
    let mut input_value =
        InputValue::new(input_value_definition.name.node.to_string(), get_type_ref(&input_value_definition.ty.node));
    if let Some(default_value) = input_value_definition.default_value {
        input_value = input_value.default_value(default_value.node);
    }
    if let Some(description) = input_value_definition.description {
        input_value = input_value.description(description.node);
    }
    input_value
}

/// "[User!]!" del SDL -> el TypeRef equivalente.
fn get_type_ref(ty: &Type) -> TypeRef {
    let type_ref = match &ty.base {
        BaseType::Named(name) => TypeRef::named(name.to_string()),
        BaseType::List(item_type) => TypeRef::List(Box::new(get_type_ref(item_type))),
    };
    if ty.nullable { type_ref } else { TypeRef::NonNull(Box::new(type_ref)) }
}

/// el ExecutionResult::toArray() de webonyx: errors (si hay) delante de data, y data solo si no es null. Cada error
/// con message, locations, path y extensions, en ese orden.
fn get_graphql_result(response: async_graphql::Response) -> Primitives {
    let mut graphql_result = Primitives::new();
    if !response.errors.is_empty() {
        let errors: Vec<Value> =
            response.errors.iter().map(|error| serde_json::to_value(error).unwrap_or(Value::Null)).collect();
        graphql_result.insert(GraphqlEnum::KEY_ERRORS.to_string(), Value::Array(errors));
    }
    let data = response.data.into_json().unwrap_or(Value::Null);
    if !data.is_null() {
        graphql_result.insert(GraphqlEnum::KEY_DATA.to_string(), data);
    }
    graphql_result
}
