package exceptions

import (
	"strings"

	"task-go/src/core/config"
	"task-go/src/modules/graphql/domain/enums"
	sharedenums "task-go/src/modules/shared/domain/enums"
)

// GraphqlSchemaException el registro de /graphql esta mal montado (src/core/routes/graphql_schema.go y los
// XxxGraphqlSchemaProvider): es un fallo de programacion, no de la peticion, asi que se responde 500 y se loguea.
type GraphqlSchemaException struct {
	message string
	code    int
}

func (e *GraphqlSchemaException) Error() string {
	return e.message
}

func (e *GraphqlSchemaException) GetCode() int {
	return e.code
}

func FieldsAlreadyHaveResolver(typeName string, fieldNames []string, schemaProviderName string) *GraphqlSchemaException {
	return &GraphqlSchemaException{
		message: config.Tr(
			enums.GraphqlMessageEnum.GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X,
			typeName,
			strings.Join(fieldNames, ", "),
			schemaProviderName,
		),
		code: sharedenums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
	}
}

func FieldsWithoutResolver(typeName string, fieldNames []string) *GraphqlSchemaException {
	return &GraphqlSchemaException{
		message: config.Tr(enums.GraphqlMessageEnum.GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER, typeName, strings.Join(fieldNames, ", ")),
		code:    sharedenums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
	}
}
