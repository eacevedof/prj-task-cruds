package controllers

// GraphqlError el GraphQL\Error de webonyx: el error que devuelve un resolver. graph-gophers lo pone en errors[] con
// su message y, por Extensions(), con extensions.code (codigo HTTP equivalente) y extensions.errors (el detalle).
type GraphqlError struct {
	message    string
	extensions map[string]any
}

func (e *GraphqlError) Error() string {
	return e.message
}

func (e *GraphqlError) Extensions() map[string]any {
	return e.extensions
}
