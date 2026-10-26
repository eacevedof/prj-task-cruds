package createuser

import (
	"slices"

	"task-go/src/core/config"
	sharedenums "task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/datetimer"
	"task-go/src/modules/shared/infrastructure/components/password"
	"task-go/src/modules/shared/infrastructure/components/token"
	"task-go/src/modules/shared/infrastructure/components/uuid"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/shared/infrastructure/traits"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
	"task-go/src/modules/users/domain/services"
	"task-go/src/modules/users/infrastructure/repositories"
)

// CreateUserService alta abierta (sin token): devuelve el primer api_token en claro; despues, el login o regenerar
// dan uno nuevo.
type CreateUserService struct {
	traits.ErrorStackTrait

	dateTimer                   *datetimer.DateTimer
	passwordHasher              *password.PasswordHasher
	tokenGenerator              *token.TokenGenerator
	usersReaderSqliteRepository *repositories.UsersReaderSqliteRepository
	usersWriterSqliteRepository *repositories.UsersWriterSqliteRepository

	createUserDto *CreateUserDto
}

func CreateUserServiceGetInstance() *CreateUserService {
	return &CreateUserService{
		dateTimer:                   datetimer.GetInstance(),
		passwordHasher:              password.GetInstance(),
		tokenGenerator:              token.GetInstance(),
		usersReaderSqliteRepository: repositories.UsersReaderSqliteRepositoryGetInstance(),
		usersWriterSqliteRepository: repositories.UsersWriterSqliteRepositoryGetInstance(),
	}
}

// Invoke error: *FieldsException (400), *UsersException (409) o uno inesperado.
func (s *CreateUserService) Invoke(createUserDto *CreateUserDto) (*CreateUserResultDto, error) {
	s.createUserDto = createUserDto

	if err := s.failIfWrongInput(); err != nil {
		return nil, err
	}
	if err := s.failIfUserNameOrEmailExists(); err != nil {
		return nil, err
	}

	passwordHash, err := s.passwordHasher.GetPasswordHash(createUserDto.GetPassword())
	if err != nil {
		return nil, err
	}
	apiToken := s.tokenGenerator.GetNewToken()
	userId, err := s.usersWriterSqliteRepository.InsertUser(
		entities.UserEntityFromPrimitives(map[string]any{
			enums.UsersKeyEnum.USER_UUID:      uuid.GetInstance().GetUniqueIdWithPrefix(entities.USER_UUID_PREFIX),
			enums.UsersKeyEnum.USER_NAME:      createUserDto.GetUserName(),
			enums.UsersKeyEnum.USER_EMAIL:     createUserDto.GetUserEmail(),
			enums.UsersKeyEnum.PASSWORD_HASH:  passwordHash,
			enums.UsersKeyEnum.API_TOKEN_HASH: s.tokenGenerator.GetTokenHash(apiToken),
			enums.UsersKeyEnum.CREATED_AT:     s.dateTimer.GetNowDtIntoTargetTz(),
		}),
	)
	if err != nil {
		return nil, err
	}

	userEntity, err := s.usersReaderSqliteRepository.GetUserById(userId)
	if err != nil {
		return nil, err
	}
	return CreateUserResultDtoFromPrimitives(map[string]any{
		"user":     entities.ToPrimitivesOrEmpty(userEntity),
		"apiToken": apiToken,
	}), nil
}

func (s *CreateUserService) failIfWrongInput() error {
	userFieldsValidator := services.UserFieldsValidatorGetInstance()
	errors := slices.Concat(
		userFieldsValidator.GetErrors(s.createUserDto.GetUserName(), s.createUserDto.GetUserEmail()),
		userFieldsValidator.GetPasswordErrors(s.createUserDto.GetPassword()),
	)
	if len(errors) == 0 {
		return nil
	}

	return sharedexceptions.FieldsExceptionBadRequestCustom(
		s.GetErrorStackMessage(
			config.Tr(sharedenums.SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors,
		))
}

func (s *CreateUserService) failIfUserNameOrEmailExists() error {
	userName := s.createUserDto.GetUserName()
	doesUserNameExist, err := s.usersReaderSqliteRepository.DoesUserNameExist(userName)
	if err != nil {
		return err
	}
	if doesUserNameExist {
		return exceptions.ConflictCustom(config.Tr(enums.UsersMessageEnum.USER_NAME_X_ALREADY_EXISTS, userName))
	}

	userEmail := s.createUserDto.GetUserEmail()
	doesUserEmailExist, err := s.usersReaderSqliteRepository.DoesUserEmailExist(userEmail)
	if err != nil {
		return err
	}
	if doesUserEmailExist {
		return exceptions.ConflictCustom(config.Tr(enums.UsersMessageEnum.EMAIL_X_ALREADY_EXISTS, userEmail))
	}
	return nil
}
