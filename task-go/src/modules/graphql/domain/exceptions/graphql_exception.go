package exceptions

import (
	"task-go/src/modules/graphql/domain/enums"
	sharedenums "task-go/src/modules/shared/domain/enums"
)

// GraphQlException lo que devuelve un resolver para que su mensaje llegue al cliente: graph-gophers la pone en
// errors[] con su mensaje tal cual y, por Extensions(), su codigo en extensions.code y su mensaje en
// extensions.errors. Es el GraphQlException de task-php (ClientAware + ProvidesExtensions de webonyx).
type GraphQlException struct {
	message string
	code    int
}

// controlledException una excepcion controlada (de dominio o FieldsException): su mensaje y su codigo HTTP
// equivalente. Es el Exception que recibe la factoria en task-php (sin depender de infrastructure).
type controlledException interface {
	error
	GetCode() int
}

func (e *GraphQlException) Error() string {
	return e.message
}

func (e *GraphQlException) GetCode() int {
	return e.code
}

func (e *GraphQlException) Extensions() map[string]any {
	return map[string]any{
		enums.GraphQlEnum.EXTENSION_CODE:   e.code,
		enums.GraphQlEnum.EXTENSION_ERRORS: []string{e.message},
	}
}

// ClientAwareExceptionForWebonyx excepcion controlada (de dominio o FieldsException): su codigo 4xx y su mensaje pasan
// tal cual al cliente. Conserva el nombre de task-php aunque aqui la libreria sea graph-gophers.
func ClientAwareExceptionForWebonyx(ex controlledException) *GraphQlException {
	return &GraphQlException{message: ex.Error(), code: ex.GetCode()}
}

// InternalServerError fallo no controlado: 500 con un mensaje generico, el original no sale (el controller ya lo ha
// logueado).
func InternalServerError(message string) *GraphQlException {
	return &GraphQlException{message: message, code: sharedenums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR}
}
