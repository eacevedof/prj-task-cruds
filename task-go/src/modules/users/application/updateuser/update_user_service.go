package updateuser

import (
	"task-go/src/core/config"
	sharedenums "task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/datetimer"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/shared/infrastructure/traits"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
	"task-go/src/modules/users/domain/services"
	"task-go/src/modules/users/infrastructure/repositories"
)

// UpdateUserService PUT: reemplazo completo, user_name y user_email obligatorios (mismas reglas que el alta).
type UpdateUserService struct {
	traits.ErrorStackTrait

	dateTimer                   *datetimer.DateTimer
	usersReaderSqliteRepository *repositories.UsersReaderSqliteRepository
	usersWriterSqliteRepository *repositories.UsersWriterSqliteRepository

	updateUserDto *UpdateUserDto
	userEntity    *entities.UserEntity
}

func UpdateUserServiceGetInstance() *UpdateUserService {
	return &UpdateUserService{
		dateTimer:                   datetimer.GetInstance(),
		usersReaderSqliteRepository: repositories.UsersReaderSqliteRepositoryGetInstance(),
		usersWriterSqliteRepository: repositories.UsersWriterSqliteRepositoryGetInstance(),
	}
}

// Invoke error: *FieldsException (400), *UsersException (401, 404, 409) o uno inesperado.
func (s *UpdateUserService) Invoke(updateUserDto *UpdateUserDto) (*UpdateUserResultDto, error) {
	s.updateUserDto = updateUserDto
	if err := s.failIfWrongInput(); err != nil {
		return nil, err
	}
	if err := s.failIfWrongFields(); err != nil {
		return nil, err
	}

	// autenticado = existe: su id ya viene resuelto
	s.userEntity = entities.UserEntityFromPrimitives(map[string]any{
		enums.UsersKeyEnum.ID:         updateUserDto.GetAuthUserId(),
		enums.UsersKeyEnum.USER_UUID:  updateUserDto.GetAuthUserUuid(),
		enums.UsersKeyEnum.USER_NAME:  updateUserDto.GetUserName(),
		enums.UsersKeyEnum.USER_EMAIL: updateUserDto.GetUserEmail(),
		enums.UsersKeyEnum.UPDATED_AT: s.dateTimer.GetNowDtIntoTargetTz(),
	})
	if err := s.failIfUserNameOrEmailExistsInOtherUser(); err != nil {
		return nil, err
	}

	if _, err := s.usersWriterSqliteRepository.UpdateUserById(s.userEntity); err != nil {
		return nil, err
	}

	userEntity, err := s.usersReaderSqliteRepository.GetUserById(s.userEntity.GetId())
	if err != nil {
		return nil, err
	}
	return UpdateUserResultDtoFromPrimitives(map[string]any{"user": entities.ToPrimitivesOrEmpty(userEntity)}), nil
}

// failIfWrongInput solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
func (s *UpdateUserService) failIfWrongInput() error {
	if s.updateUserDto.GetAuthUserId() < 1 {
		return exceptions.UnauthorizedCustom(config.Tr(enums.UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN))
	}
	userUuid := s.updateUserDto.GetUserUuid()
	if userUuid != s.updateUserDto.GetAuthUserUuid() {
		return exceptions.NotFoundCustom(config.Tr(enums.UsersMessageEnum.USER_X_NOT_FOUND, userUuid))
	}
	return nil
}

func (s *UpdateUserService) failIfWrongFields() error {
	errors := services.UserFieldsValidatorGetInstance().GetErrors(
		s.updateUserDto.GetUserName(),
		s.updateUserDto.GetUserEmail(),
	)
	if len(errors) == 0 {
		return nil
	}

	return sharedexceptions.FieldsExceptionBadRequestCustom(
		s.GetErrorStackMessage(
			config.Tr(sharedenums.SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors,
		))
}

func (s *UpdateUserService) failIfUserNameOrEmailExistsInOtherUser() error {
	doesUserNameExist, err := s.usersReaderSqliteRepository.DoesUserNameExistInOtherUser(s.userEntity)
	if err != nil {
		return err
	}
	if doesUserNameExist {
		return exceptions.ConflictCustom(
			config.Tr(enums.UsersMessageEnum.USER_NAME_X_ALREADY_EXISTS, s.userEntity.GetUserName()),
		)
	}

	doesUserEmailExist, err := s.usersReaderSqliteRepository.DoesUserEmailExistInOtherUser(s.userEntity)
	if err != nil {
		return err
	}
	if doesUserEmailExist {
		return exceptions.ConflictCustom(
			config.Tr(enums.UsersMessageEnum.EMAIL_X_ALREADY_EXISTS, s.userEntity.GetUserEmail()),
		)
	}
	return nil
}
