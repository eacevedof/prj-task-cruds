package enums

// GraphqlMessageEnum msgid del modulo Graphql.
var GraphqlMessageEnum = struct {
	GRAPHQL_QUERY_IS_REQUIRED                  string
	GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X string
	GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER        string
}{
	GRAPHQL_QUERY_IS_REQUIRED:                  "GraphQL query is required",
	GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X: "graphql: {0}.{1} already has a resolver ({2})",
	GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER:        "graphql: {0}.{1} has no resolver",
}
