package deleteuser

import (
	"task-go/src/core/config"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
	"task-go/src/modules/users/infrastructure/repositories"
)

// DeleteUserService borrado fisico. Las tasks del usuario se borran con el (FK ON DELETE CASCADE).
type DeleteUserService struct {
	usersWriterSqliteRepository *repositories.UsersWriterSqliteRepository

	deleteUserDto *DeleteUserDto
}

func DeleteUserServiceGetInstance() *DeleteUserService {
	return &DeleteUserService{
		usersWriterSqliteRepository: repositories.UsersWriterSqliteRepositoryGetInstance(),
	}
}

// Invoke error: *UsersException (401, 404) o uno inesperado.
func (s *DeleteUserService) Invoke(deleteUserDto *DeleteUserDto) (*DeleteUserResultDto, error) {
	s.deleteUserDto = deleteUserDto
	if err := s.failIfWrongInput(); err != nil {
		return nil, err
	}

	affected, err := s.usersWriterSqliteRepository.DeleteUserById(
		entities.UserEntityFromPrimitives(map[string]any{
			enums.UsersKeyEnum.ID:        deleteUserDto.GetAuthUserId(),
			enums.UsersKeyEnum.USER_UUID: deleteUserDto.GetAuthUserUuid(),
		}),
	)
	if err != nil {
		return nil, err
	}

	return DeleteUserResultDtoFromPrimitives(map[string]any{
		"userUuid": deleteUserDto.GetUserUuid(),
		"affected": affected,
	}), nil
}

// failIfWrongInput solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
func (s *DeleteUserService) failIfWrongInput() error {
	if s.deleteUserDto.GetAuthUserId() < 1 {
		return exceptions.UnauthorizedCustom(config.Tr(enums.UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN))
	}
	userUuid := s.deleteUserDto.GetUserUuid()
	if userUuid != s.deleteUserDto.GetAuthUserUuid() {
		return exceptions.NotFoundCustom(config.Tr(enums.UsersMessageEnum.USER_X_NOT_FOUND, userUuid))
	}
	return nil
}
