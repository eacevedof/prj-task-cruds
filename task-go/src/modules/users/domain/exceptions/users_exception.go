package exceptions

import "task-go/src/modules/shared/domain/enums"

// UsersException error de dominio de users con su codigo HTTP equivalente (401, 404, 409).
type UsersException struct {
	message string
	code    int
}

func (e *UsersException) Error() string {
	return e.message
}

func (e *UsersException) GetCode() int {
	return e.code
}

func UnauthorizedCustom(message string) *UsersException {
	return &UsersException{message: message, code: enums.ExceptionEnum.CODE_UNAUTHORIZED}
}

func NotFoundCustom(message string) *UsersException {
	return &UsersException{message: message, code: enums.ExceptionEnum.CODE_NOT_FOUND}
}

func ConflictCustom(message string) *UsersException {
	return &UsersException{message: message, code: enums.ExceptionEnum.CODE_CONFLICT}
}
