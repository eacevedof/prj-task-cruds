use async_graphql::dynamic::{
    Field, FieldFuture, FieldValue, InputObject, InputValue, Object, ResolverContext, Schema, TypeRef,
};
use async_graphql::parser::parse_schema;
use async_graphql::parser::types::{BaseType, InputValueDefinition, Type, TypeKind, TypeSystemDefinition};
use async_graphql::{PathSegment, ServerError};

/// el resolver de un campo: (tipo, campo, contexto) -> su valor. Uno solo para todo el schema: decide por tipo.campo
/// (ver graphql_resolvers.rs).
pub type FieldResolver = for<'a> fn(&str, &str, &ResolverContext<'a>) -> async_graphql::Result<Option<FieldValue<'a>>>;

/// el schema ejecutable a partir de schema.graphql (el mismo fichero que task-php): async-graphql construye los
/// schemas en codigo, asi que aqui se lee el SDL y se da de alta cada tipo, campo y argumento (con su descripcion y
/// su default). Todos los campos los resuelve field_resolver. En task-php lo hace BuildSchema::build() de webonyx.
pub fn get_schema_from_sdl(sdl: &str, field_resolver: FieldResolver, max_query_depth: usize) -> Result<Schema, String> {
    let service_document = parse_schema(sdl).map_err(|error| format!("schema.graphql: {error}"))?;

    let mut schema_builder = Schema::build("Query", Some("Mutation"), None).limit_depth(max_query_depth);
    for type_system_definition in service_document.definitions {
        let TypeSystemDefinition::Type(type_definition) = type_system_definition else {
            continue;
        };
        let type_definition = type_definition.node;
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
                    let (resolver_type_name, resolver_field_name) = (type_name.clone(), field_name.clone());
                    let is_nullable = field_definition.ty.node.nullable;
                    let mut field = Field::new(
                        field_name,
                        get_type_ref(&field_definition.ty.node),
                        move |resolver_context: ResolverContext<'_>| {
                            let field_value =
                                match field_resolver(&resolver_type_name, &resolver_field_name, &resolver_context) {
                                    // como webonyx: el error va a errors[] con su path y el campo queda a null (el
                                    // resto de la consulta sigue). Un campo no nulo no puede: su error corta la consulta
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
            // schema.graphql solo define objetos e inputs: los escalares son los de GraphQL (String, Int, ID)
            _ => {}
        }
    }
    schema_builder.finish().map_err(|error| format!("schema.graphql: {error}"))
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
