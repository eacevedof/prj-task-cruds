package authenticateuser

import (
	"task-go/src/core/config"
	"task-go/src/modules/shared/infrastructure/components/token"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
	"task-go/src/modules/users/infrastructure/repositories"
)

// AuthenticateUserService Bearer token -> usuario. Mismo error para token ausente y token desconocido: no se da pista
// de cual fallo.
type AuthenticateUserService struct {
	tokenGenerator              *token.TokenGenerator
	usersReaderSqliteRepository *repositories.UsersReaderSqliteRepository

	authenticateUserDto *AuthenticateUserDto
}

func AuthenticateUserServiceGetInstance() *AuthenticateUserService {
	return &AuthenticateUserService{
		tokenGenerator:              token.GetInstance(),
		usersReaderSqliteRepository: repositories.UsersReaderSqliteRepositoryGetInstance(),
	}
}

// Invoke error: *UsersException (401) o uno inesperado.
func (s *AuthenticateUserService) Invoke(authenticateUserDto *AuthenticateUserDto) (*AuthenticateUserResultDto, error) {
	s.authenticateUserDto = authenticateUserDto
	if err := s.failIfWrongInput(); err != nil {
		return nil, err
	}

	userEntity, err := s.tryToGetUserByApiTokenOrFail()
	if err != nil {
		return nil, err
	}

	return AuthenticateUserResultDtoFromPrimitives(map[string]any{
		"userId":   userEntity.GetId(),
		"userUuid": userEntity.GetUserUuid(),
	}), nil
}

func (s *AuthenticateUserService) failIfWrongInput() error {
	if s.authenticateUserDto.GetApiToken() == "" {
		return exceptions.UnauthorizedCustom(config.Tr(enums.UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN))
	}
	return nil
}

func (s *AuthenticateUserService) tryToGetUserByApiTokenOrFail() (*entities.UserEntity, error) {
	userEntity, err := s.usersReaderSqliteRepository.GetUserByApiTokenHash(
		s.tokenGenerator.GetTokenHash(s.authenticateUserDto.GetApiToken()),
	)
	if err != nil {
		return nil, err
	}
	if userEntity == nil {
		return nil, exceptions.UnauthorizedCustom(config.Tr(enums.UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN))
	}
	return userEntity, nil
}
