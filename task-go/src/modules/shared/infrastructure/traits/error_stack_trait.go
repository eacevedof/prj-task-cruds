package traits

import "strings"

// ErrorStackTrait un servicio no guarda errores: si falla, escala con su excepcion y ahi acaba. Este trait solo da
// formato a una pila de errores (p.ej. la de un validador) para meterla en el mensaje de esa excepcion:
// exceptions.FieldsExceptionBadRequestCustom(s.GetErrorStackMessage(config.Tr(FIELDS_VALIDATION_ERRORS), errors))
type ErrorStackTrait struct{}

// GetErrorStackMessage "Fields validation errors: user_name: field is required; user_email: invalid email"
func (t ErrorStackTrait) GetErrorStackMessage(title string, errorStack []string) string {
	return title + ": " + strings.Join(errorStack, "; ")
}
