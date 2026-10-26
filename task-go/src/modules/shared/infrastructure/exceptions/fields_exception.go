package exceptions

import "task-go/src/modules/shared/domain/enums"

// FieldsException validacion de campos: 400 con el detalle por campo ya metido en el mensaje
// (ErrorStackTrait.GetErrorStackMessage).
type FieldsException struct {
	message string
	code    int
}

func (e *FieldsException) Error() string {
	return e.message
}

func (e *FieldsException) GetCode() int {
	return e.code
}

func FieldsExceptionBadRequestCustom(message string) *FieldsException {
	return &FieldsException{message: message, code: enums.ExceptionEnum.CODE_BAD_REQUEST}
}
