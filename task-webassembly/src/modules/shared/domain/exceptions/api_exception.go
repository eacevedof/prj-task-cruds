package exceptions

// ApiResponseInterface lo que una excepcion lee de la respuesta de la API cuando viene con status false
// (lo implementa graphqlrequester.ApiResponse: el dominio no conoce la infraestructura).
type ApiResponseInterface interface {
	GetCode() int
	GetErrors() []string
}

// ApiException base de las excepciones de dominio de cada modulo: un fallo controlado con su codigo HTTP y el
// detalle por campo (errors) tal como lo devuelve la API. La embeben UsersException, AccountException y TasksException.
type ApiException struct {
	message string
	code    int
	errors  []string
}

func NewApiException(message string, code int, errors []string) ApiException {
	if len(errors) == 0 {
		errors = []string{message}
	}
	return ApiException{message: message, code: code, errors: errors}
}

func (e *ApiException) Error() string {
	return e.message
}

func (e *ApiException) GetCode() int {
	return e.code
}

func (e *ApiException) GetErrors() []string {
	return e.errors
}
