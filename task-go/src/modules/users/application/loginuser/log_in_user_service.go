package loginuser

import (
	"task-go/src/core/config"
	"task-go/src/modules/shared/infrastructure/components/datetimer"
	"task-go/src/modules/shared/infrastructure/components/password"
	"task-go/src/modules/shared/infrastructure/components/token"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
	"task-go/src/modules/users/infrastructure/repositories"
)

// LogInUserService login con user_name + password: devuelve un api_token NUEVO y el anterior deja de valer (como
// regenerar). Usuario inexistente, sin password o password erronea responden lo mismo (401, mismo mensaje y mismo
// tiempo): no se revela que user_name existen.
type LogInUserService struct {
	dateTimer                   *datetimer.DateTimer
	passwordHasher              *password.PasswordHasher
	tokenGenerator              *token.TokenGenerator
	usersReaderSqliteRepository *repositories.UsersReaderSqliteRepository
	usersWriterSqliteRepository *repositories.UsersWriterSqliteRepository

	logInUserDto *LogInUserDto
	passwordHash string
}

func LogInUserServiceGetInstance() *LogInUserService {
	return &LogInUserService{
		dateTimer:                   datetimer.GetInstance(),
		passwordHasher:              password.GetInstance(),
		tokenGenerator:              token.GetInstance(),
		usersReaderSqliteRepository: repositories.UsersReaderSqliteRepositoryGetInstance(),
		usersWriterSqliteRepository: repositories.UsersWriterSqliteRepositoryGetInstance(),
	}
}

// Invoke error: *UsersException (401) o uno inesperado.
func (s *LogInUserService) Invoke(logInUserDto *LogInUserDto) (*LogInUserResultDto, error) {
	s.logInUserDto = logInUserDto
	if err := s.failIfWrongInput(); err != nil {
		return nil, err
	}

	userEntity, err := s.tryToGetUserByCredentialsOrFail()
	if err != nil {
		return nil, err
	}
	userId := userEntity.GetId()
	now := s.dateTimer.GetNowDtIntoTargetTz()
	if err := s.updatePasswordHashIfNeedsRehash(userId, now); err != nil {
		return nil, err
	}

	apiToken := s.tokenGenerator.GetNewToken()
	_, err = s.usersWriterSqliteRepository.UpdateApiTokenHashById(
		entities.UserEntityFromPrimitives(map[string]any{
			enums.UsersKeyEnum.ID:             userId,
			enums.UsersKeyEnum.API_TOKEN_HASH: s.tokenGenerator.GetTokenHash(apiToken),
			enums.UsersKeyEnum.UPDATED_AT:     now,
		}),
	)
	if err != nil {
		return nil, err
	}

	// releido: el login cambia updated_at
	loggedInUserEntity, err := s.usersReaderSqliteRepository.GetUserById(userId)
	if err != nil {
		return nil, err
	}
	return LogInUserResultDtoFromPrimitives(map[string]any{
		"user":     entities.ToPrimitivesOrEmpty(loggedInUserEntity),
		"apiToken": apiToken,
	}), nil
}

// failIfWrongInput sin user_name o sin password no hay nada que comprobar: mismo 401 que unas credenciales erroneas.
func (s *LogInUserService) failIfWrongInput() error {
	if s.logInUserDto.GetUserName() == "" || s.logInUserDto.GetPassword() == "" {
		return exceptions.UnauthorizedCustom(config.Tr(enums.UsersMessageEnum.INVALID_USER_NAME_OR_PASSWORD))
	}
	return nil
}

// tryToGetUserByCredentialsOrFail el hash vacio (usuario inexistente o sin password) tambien pasa por la
// verificacion: mismo tiempo de respuesta que una password erronea.
func (s *LogInUserService) tryToGetUserByCredentialsOrFail() (*entities.UserEntity, error) {
	userEntity, err := s.usersReaderSqliteRepository.GetUserByUserName(s.logInUserDto.GetUserName())
	if err != nil {
		return nil, err
	}
	s.passwordHash = ""
	if userEntity != nil {
		if s.passwordHash, err = s.usersReaderSqliteRepository.GetPasswordHashByUserId(userEntity.GetId()); err != nil {
			return nil, err
		}
	}
	if !s.passwordHasher.IsPasswordValid(s.logInUserDto.GetPassword(), s.passwordHash) {
		return nil, exceptions.UnauthorizedCustom(config.Tr(enums.UsersMessageEnum.INVALID_USER_NAME_OR_PASSWORD))
	}
	return userEntity, nil
}

func (s *LogInUserService) updatePasswordHashIfNeedsRehash(userId int, now string) error {
	if !s.passwordHasher.DoesPasswordHashNeedRehash(s.passwordHash) {
		return nil
	}

	passwordHash, err := s.passwordHasher.GetPasswordHash(s.logInUserDto.GetPassword())
	if err != nil {
		return err
	}
	_, err = s.usersWriterSqliteRepository.UpdatePasswordHashById(
		entities.UserEntityFromPrimitives(map[string]any{
			enums.UsersKeyEnum.ID:            userId,
			enums.UsersKeyEnum.PASSWORD_HASH: passwordHash,
			enums.UsersKeyEnum.UPDATED_AT:    now,
		}),
	)
	return err
}
