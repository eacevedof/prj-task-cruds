package exceptions

import (
	"strings"

	"task-go/src/core/config"
	"task-go/src/modules/graphql/domain/enums"
	sharedenums "task-go/src/modules/shared/domain/enums"
)

// GraphQlSchemaException el registro de /graphql esta mal montado: es un fallo del programador, no de la peticion.
// Salta al montar el schema, que en Go se monta una vez por proceso (la primera peticion a /graphql) y se queda
// guardado con su error, asi que rompe TODAS las llamadas a /graphql (aunque la query no toque el campo afectado).
// La recoge ExecuteGraphQlQueryController: el mensaje va al log y el cliente solo recibe un 500 con el texto
// generico, sin data.
type GraphQlSchemaException struct {
	message string
	code    int
}

func (e *GraphQlSchemaException) Error() string {
	return e.message
}

func (e *GraphQlSchemaException) GetCode() int {
	return e.code
}

// FieldsAlreadyHaveResolver dos providers registran resolver para el mismo tipo.campo: el segundo pisaria al primero
// sin avisar. La devuelve GraphQlSchemaProvidersRegistry.GetAllMergedResolversByType() al unir los providers en el
// orden del registro (src/core/routes/graphql_schema_providers.go).
//
// Ejemplo: TasksGraphQlSchemaProvider anade por error "user" en Query, que ya es de Users
//
//	typeName           "Query"
//	fieldNames         ["user"]  (o varios: ["tasks", "task"] -> "Query.tasks, task")
//	schemaProviderName "*providers.TasksGraphQlSchemaProvider"
//	                   (el que llega SEGUNDO y choca, no el que ya tenia el campo)
//	mensaje: graphql: Query.user already has a resolver (*providers.TasksGraphQlSchemaProvider)
func FieldsAlreadyHaveResolver(typeName string, fieldNames []string, schemaProviderName string) *GraphQlSchemaException {
	return &GraphQlSchemaException{
		message: config.Tr(
			enums.GraphQlMessageEnum.GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X,
			typeName,
			strings.Join(fieldNames, ", "),
			schemaProviderName,
		),
		code: sharedenums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
	}
}

// FieldsWithoutResolver un campo de Query o Mutation esta en el .graphql pero ningun provider le da resolver: sin esto
// devolveria null sin avisar. La devuelve ExecuteGraphQlQueryService (failIfRootFieldWithoutResolver) tras montar el
// schema. Solo se comprueban los tipos raiz: un campo de un tipo de negocio sin resolver (p. ej. User.tasks) no salta
// aqui, lo resuelve el resolver por defecto leyendo del map padre (y daria null).
//
// Ejemplo: se anade "archiveTask(task_uuid: ID!): Task" al "extend type Mutation" de tasks.graphql y se olvida el
// resolver en TasksGraphQlSchemaProvider.getMutationControllers()
//
//	typeName   "Mutation" (solo GraphQlEnum.TYPE_QUERY o TYPE_MUTATION)
//	fieldNames ["archiveTask"]
//	mensaje: graphql: Mutation.archiveTask has no resolver
func FieldsWithoutResolver(typeName string, fieldNames []string) *GraphQlSchemaException {
	return &GraphQlSchemaException{
		message: config.Tr(
			enums.GraphQlMessageEnum.GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER,
			typeName,
			strings.Join(fieldNames, ", "),
		),
		code: sharedenums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
	}
}
