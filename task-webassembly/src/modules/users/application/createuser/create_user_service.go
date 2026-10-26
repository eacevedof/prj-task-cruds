package createuser

import (
	"task-webassembly/src/modules/shared/application"
	"task-webassembly/src/modules/users/domain/enums"
	"task-webassembly/src/modules/users/domain/exceptions"
	"task-webassembly/src/modules/users/infrastructure/repositories"
)

// CreateUserService alta abierta: la API devuelve el usuario con su api_token. Aqui solo se exige que lleguen los
// campos: el resto de reglas (formato de user_name, longitud de la password, user_name y email unicos) las aplica ella.
type CreateUserService struct {
	usersWriterGraphqlRepository *repositories.UsersWriterGraphqlRepository
}

func CreateUserServiceGetInstance() *CreateUserService {
	return &CreateUserService{
		usersWriterGraphqlRepository: repositories.UsersWriterGraphqlRepositoryGetInstance(),
	}
}

// Invoke error: *UsersException o uno inesperado (red, respuesta que no es JSON).
func (s *CreateUserService) Invoke(createUserDto *CreateUserDto) (*CreateUserResultDto, error) {
	errors := []string{}
	if createUserDto.GetUserName() == "" {
		errors = append(errors, enums.UsersMessageEnum.USER_NAME_IS_REQUIRED)
	}
	if createUserDto.GetUserEmail() == "" {
		errors = append(errors, enums.UsersMessageEnum.USER_EMAIL_IS_REQUIRED)
	}
	if createUserDto.GetPassword() == "" {
		errors = append(errors, enums.UsersMessageEnum.PASSWORD_IS_REQUIRED)
	}
	if len(errors) > 0 {
		return nil, exceptions.BadRequestCustom(errors)
	}

	apiResponse, err := s.usersWriterGraphqlRepository.CreateUser(
		createUserDto.GetUserName(),
		createUserDto.GetUserEmail(),
		createUserDto.GetPassword(),
	)
	if err != nil {
		return nil, err
	}
	if !apiResponse.Status {
		return nil, exceptions.FromApiResponse(apiResponse)
	}

	user := application.ToMap(apiResponse.Data)
	return CreateUserResultDtoFromPrimitives(map[string]any{
		"apiToken":  user[enums.UsersKeyEnum.API_TOKEN],
		"userUuid":  user[enums.UsersKeyEnum.USER_UUID],
		"userName":  user[enums.UsersKeyEnum.USER_NAME],
		"userEmail": user[enums.UsersKeyEnum.USER_EMAIL],
	}), nil
}
