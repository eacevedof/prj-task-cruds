package exceptions

import (
	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	sharedexceptions "task-webassembly/src/modules/shared/domain/exceptions"
)

// UsersException el controller la distingue de un error inesperado con errors.As (el instanceof de task-js).
type UsersException struct {
	sharedexceptions.ApiException
}

func BadRequestCustom(errors []string) *UsersException {
	return &UsersException{
		ApiException: sharedexceptions.NewApiException(errors[0], sharedenums.HttpCodeEnum.BAD_REQUEST, errors),
	}
}

// FromApiResponse la API respondio con status false: su codigo y su detalle viajan tal cual.
func FromApiResponse(apiResponse sharedexceptions.ApiResponseInterface) *UsersException {
	errors := apiResponse.GetErrors()
	message := ""
	if len(errors) > 0 {
		message = errors[0]
	}
	return &UsersException{
		ApiException: sharedexceptions.NewApiException(message, apiResponse.GetCode(), errors),
	}
}
