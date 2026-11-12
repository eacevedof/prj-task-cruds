package providers

import "task-go/src/modules/shared/infrastructure/components/httprequester"

// InterfaceGraphQlSchemaProvider lo que un modulo aporta a /graphql: su parte del schema y quien resuelve cada campo.
// Cada modulo tiene el suyo en infrastructure/providers y se registra en src/core/routes/graphql_schema_providers.go.
type InterfaceGraphQlSchemaProvider interface {
	// GetGraphQlFileContent SDL del modulo (su <modulo>.graphql): define tipos o extiende los de otro modulo con
	// "extend type".
	GetGraphQlFileContent() string

	// GetControllerResolvers tipo => campo => resolver, en orden (el orden sale en el mensaje de un campo repetido).
	GetControllerResolvers() []GraphQlTypeResolvers
}

// GraphQlResolver el fn(array $gqlPayload, mixed $parent) de task-php: devuelve el ResultDto (su ToArray) o una
// GraphQlException. parent es el valor del tipo padre como map (vacio en Query y Mutation). El controller se crea al
// llamarse, con el httpRequester de la peticion en curso.
type GraphQlResolver func(httpRequester *httprequester.HttpRequester, gqlPayload map[string]any, parent map[string]any) (any, error)

// GraphQlTypeResolvers los resolvers de un tipo (Query, Mutation, User...).
type GraphQlTypeResolvers struct {
	TypeName       string
	FieldResolvers []GraphQlFieldResolver
}

type GraphQlFieldResolver struct {
	FieldName string
	Resolver  GraphQlResolver
}
