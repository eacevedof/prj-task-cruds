package getuser

import (
	"task-go/src/core/config"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
	"task-go/src/modules/users/infrastructure/repositories"
)

type GetUserService struct {
	usersReaderSqliteRepository *repositories.UsersReaderSqliteRepository

	getUserDto *GetUserDto
}

func GetUserServiceGetInstance() *GetUserService {
	return &GetUserService{
		usersReaderSqliteRepository: repositories.UsersReaderSqliteRepositoryGetInstance(),
	}
}

// Invoke error: *UsersException (401, 404) o uno inesperado.
func (s *GetUserService) Invoke(getUserDto *GetUserDto) (*GetUserResultDto, error) {
	s.getUserDto = getUserDto
	if err := s.failIfWrongInput(); err != nil {
		return nil, err
	}

	userEntity, err := s.usersReaderSqliteRepository.GetUserById(getUserDto.GetAuthUserId())
	if err != nil {
		return nil, err
	}
	if userEntity == nil {
		return nil, exceptions.NotFoundCustom(config.Tr(enums.UsersMessageEnum.USER_X_NOT_FOUND, getUserDto.GetUserUuid()))
	}

	return GetUserResultDtoFromPrimitives(map[string]any{"user": userEntity.ToPrimitives()}), nil
}

// failIfWrongInput solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
func (s *GetUserService) failIfWrongInput() error {
	if s.getUserDto.GetAuthUserId() == 0 {
		return exceptions.UnauthorizedCustom(config.Tr(enums.UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN))
	}
	userUuid := s.getUserDto.GetUserUuid()
	if userUuid != s.getUserDto.GetAuthUserUuid() {
		return exceptions.NotFoundCustom(config.Tr(enums.UsersMessageEnum.USER_X_NOT_FOUND, userUuid))
	}
	return nil
}
