package listusers

import (
	"task-go/src/core/config"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
	"task-go/src/modules/users/infrastructure/repositories"
)

// ListUsersService cada token solo ve su propio usuario: la lista tiene como mucho un elemento.
type ListUsersService struct {
	usersReaderSqliteRepository *repositories.UsersReaderSqliteRepository

	listUsersDto *ListUsersDto
}

func ListUsersServiceGetInstance() *ListUsersService {
	return &ListUsersService{
		usersReaderSqliteRepository: repositories.UsersReaderSqliteRepositoryGetInstance(),
	}
}

// Invoke error: *UsersException (401) o uno inesperado.
func (s *ListUsersService) Invoke(listUsersDto *ListUsersDto) (*ListUsersResultDto, error) {
	s.listUsersDto = listUsersDto
	if err := s.failIfWrongInput(); err != nil {
		return nil, err
	}

	userEntity, err := s.usersReaderSqliteRepository.GetUserById(listUsersDto.GetAuthUserId())
	if err != nil {
		return nil, err
	}
	users := []entities.UserPrimitives{}
	if userEntity != nil {
		users = append(users, userEntity.ToPrimitives())
	}
	return ListUsersResultDtoFromPrimitives(map[string]any{"users": users}), nil
}

func (s *ListUsersService) failIfWrongInput() error {
	if s.listUsersDto.GetAuthUserId() < 1 {
		return exceptions.UnauthorizedCustom(config.Tr(enums.UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN))
	}
	return nil
}
