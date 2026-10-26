package exceptions

import "task-go/src/modules/shared/domain/enums"

// TasksException error de dominio de tasks con su codigo HTTP equivalente (401, 404).
type TasksException struct {
	message string
	code    int
}

func (e *TasksException) Error() string {
	return e.message
}

func (e *TasksException) GetCode() int {
	return e.code
}

func UnauthorizedCustom(message string) *TasksException {
	return &TasksException{message: message, code: enums.ExceptionEnum.CODE_UNAUTHORIZED}
}

func NotFoundCustom(message string) *TasksException {
	return &TasksException{message: message, code: enums.ExceptionEnum.CODE_NOT_FOUND}
}
