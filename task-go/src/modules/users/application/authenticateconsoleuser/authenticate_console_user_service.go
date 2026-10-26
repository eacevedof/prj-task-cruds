package authenticateconsoleuser

import (
	"task-go/src/core/config"
	"task-go/src/modules/users/application/authenticateuser"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
	"task-go/src/modules/users/infrastructure/repositories"
)

// AuthenticateConsoleUserService misma salida que AuthenticateUser para que los casos de uso no distingan si la
// peticion llega por API o por consola.
type AuthenticateConsoleUserService struct {
	usersReaderSqliteRepository *repositories.UsersReaderSqliteRepository

	authenticateConsoleUserDto *AuthenticateConsoleUserDto
}

func AuthenticateConsoleUserServiceGetInstance() *AuthenticateConsoleUserService {
	return &AuthenticateConsoleUserService{
		usersReaderSqliteRepository: repositories.UsersReaderSqliteRepositoryGetInstance(),
	}
}

// Invoke error: *UsersException (404) o uno inesperado.
func (s *AuthenticateConsoleUserService) Invoke(
	authenticateConsoleUserDto *AuthenticateConsoleUserDto,
) (*authenticateuser.AuthenticateUserResultDto, error) {
	s.authenticateConsoleUserDto = authenticateConsoleUserDto
	if err := s.failIfWrongInput(); err != nil {
		return nil, err
	}

	userEntity, err := s.tryToGetUserOrFail()
	if err != nil {
		return nil, err
	}

	return authenticateuser.AuthenticateUserResultDtoFromPrimitives(map[string]any{
		"userId":   userEntity.GetId(),
		"userUuid": userEntity.GetUserUuid(),
	}), nil
}

func (s *AuthenticateConsoleUserService) failIfWrongInput() error {
	if s.authenticateConsoleUserDto.GetUserUuid() == "" {
		return exceptions.NotFoundCustom(config.Tr(enums.UsersMessageEnum.USER_X_NOT_FOUND, ""))
	}
	return nil
}

func (s *AuthenticateConsoleUserService) tryToGetUserOrFail() (*entities.UserEntity, error) {
	userUuid := s.authenticateConsoleUserDto.GetUserUuid()
	userEntity, err := s.usersReaderSqliteRepository.GetUserByUserUuid(userUuid)
	if err != nil {
		return nil, err
	}
	if userEntity == nil {
		return nil, exceptions.NotFoundCustom(config.Tr(enums.UsersMessageEnum.USER_X_NOT_FOUND, userUuid))
	}
	return userEntity, nil
}
