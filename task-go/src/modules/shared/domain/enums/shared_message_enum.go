package enums

// SharedMessageEnum msgid comunes (los que se usan; el resto de task-php es herencia sin uso).
var SharedMessageEnum = struct {
	AN_UNEXPECTED_ERROR_HAS_OCCURRED string
	FIELDS_VALIDATION_ERRORS         string
	GRAPHQL_QUERY_IS_REQUIRED        string
}{
	AN_UNEXPECTED_ERROR_HAS_OCCURRED: "An unexpected error has occurred",
	FIELDS_VALIDATION_ERRORS:         "Fields validation errors",
	GRAPHQL_QUERY_IS_REQUIRED:        "GraphQL query is required",
}
