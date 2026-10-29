/// msgid del modulo GraphQL.
pub struct GraphqlMessageEnum;

impl GraphqlMessageEnum {
    pub const GRAPHQL_QUERY_IS_REQUIRED: &'static str = "GraphQL query is required";
    pub const GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X: &'static str =
        "graphql: {0}.{1} already has a resolver ({2})";
    pub const GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER: &'static str = "graphql: {0}.{1} has no resolver";
}
