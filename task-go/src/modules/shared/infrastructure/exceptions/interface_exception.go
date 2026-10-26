package exceptions

// InterfaceException lo que tienen en comun las excepciones controladas (FieldsException, UsersException,
// TasksException): un mensaje para el cliente y el codigo HTTP equivalente. Es el getMessage()/getCode() de PHP.
type InterfaceException interface {
	error
	GetCode() int
}
