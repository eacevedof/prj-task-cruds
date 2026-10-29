package schema

import "task-go/src/modules/shared/infrastructure/components/httprequester"

// InterfaceGraphqlSchemaProvider lo que un modulo aporta a /graphql: su parte del schema y quien resuelve cada campo.
// Cada modulo tiene el suyo en infrastructure/providers y se registra en src/core/routes/graphql_schema.go.
type InterfaceGraphqlSchemaProvider interface {
	// GetSdl SDL del modulo (su <modulo>.graphql): define tipos o extiende los de otro modulo con "extend type".
	GetSdl() string

	// GetResolversByType tipo => campo => resolver, en orden (el orden sale en el mensaje de un campo repetido).
	GetResolversByType() []GraphqlTypeResolvers
}

// GraphqlResolver el fn(array $args, mixed $parent) de task-php: devuelve el ResultDto (su ToArray) o un GraphqlError.
// parent es el valor del tipo padre como map (vacio en Query y Mutation). El controller se crea al llamarse, con el
// httpRequester de la peticion en curso.
type GraphqlResolver func(httpRequester *httprequester.HttpRequester, args map[string]any, parent map[string]any) (any, error)

// GraphqlTypeResolvers los resolvers de un tipo (Query, Mutation, User...).
type GraphqlTypeResolvers struct {
	TypeName       string
	FieldResolvers []GraphqlFieldResolver
}

type GraphqlFieldResolver struct {
	FieldName string
	Resolver  GraphqlResolver
}
