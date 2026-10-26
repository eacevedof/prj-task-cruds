package login

import (
	"task-webassembly/src/modules/shared/application"
	"task-webassembly/src/modules/users/domain/enums"
	"task-webassembly/src/modules/users/domain/exceptions"
	"task-webassembly/src/modules/users/infrastructure/repositories"
)

// LogInService entrar con user_name + password: la API devuelve el usuario y un api_token nuevo (el anterior deja de valer).
type LogInService struct {
	usersWriterGraphqlRepository *repositories.UsersWriterGraphqlRepository
}

func LogInServiceGetInstance() *LogInService {
	return &LogInService{
		usersWriterGraphqlRepository: repositories.UsersWriterGraphqlRepositoryGetInstance(),
	}
}

// Invoke error: *UsersException o uno inesperado (red, respuesta que no es JSON).
func (s *LogInService) Invoke(logInDto *LogInDto) (*LogInResultDto, error) {
	errors := []string{}
	if logInDto.GetUserName() == "" {
		errors = append(errors, enums.UsersMessageEnum.USER_NAME_IS_REQUIRED)
	}
	if logInDto.GetPassword() == "" {
		errors = append(errors, enums.UsersMessageEnum.PASSWORD_IS_REQUIRED)
	}
	if len(errors) > 0 {
		return nil, exceptions.BadRequestCustom(errors)
	}

	apiResponse, err := s.usersWriterGraphqlRepository.LogIn(logInDto.GetUserName(), logInDto.GetPassword())
	if err != nil {
		return nil, err
	}
	if !apiResponse.Status {
		return nil, exceptions.FromApiResponse(apiResponse)
	}

	user := application.ToMap(apiResponse.Data)
	return LogInResultDtoFromPrimitives(map[string]any{
		"apiToken":  user[enums.UsersKeyEnum.API_TOKEN],
		"userUuid":  user[enums.UsersKeyEnum.USER_UUID],
		"userName":  user[enums.UsersKeyEnum.USER_NAME],
		"userEmail": user[enums.UsersKeyEnum.USER_EMAIL],
	}), nil
}
