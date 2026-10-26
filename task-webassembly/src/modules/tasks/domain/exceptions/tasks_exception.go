package exceptions

import (
	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	sharedexceptions "task-webassembly/src/modules/shared/domain/exceptions"
)

// TasksException el controller la distingue de un error inesperado con errors.As (el instanceof de task-js).
type TasksException struct {
	sharedexceptions.ApiException
}

func BadRequestCustom(errors []string) *TasksException {
	return &TasksException{
		ApiException: sharedexceptions.NewApiException(errors[0], sharedenums.HttpCodeEnum.BAD_REQUEST, errors),
	}
}

// FromApiResponse la API respondio con status false: su codigo y su detalle viajan tal cual.
func FromApiResponse(apiResponse sharedexceptions.ApiResponseInterface) *TasksException {
	errors := apiResponse.GetErrors()
	message := ""
	if len(errors) > 0 {
		message = errors[0]
	}
	return &TasksException{
		ApiException: sharedexceptions.NewApiException(message, apiResponse.GetCode(), errors),
	}
}
