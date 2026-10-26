package listusers

import "task-go/src/modules/users/domain/entities"

type ListUsersResultDto struct {
	// lista de UserEntity.ToPrimitives
	users []entities.UserPrimitives
}

func ListUsersResultDtoFromPrimitives(primitives map[string]any) *ListUsersResultDto {
	users, _ := primitives["users"].([]entities.UserPrimitives)
	return &ListUsersResultDto{users: users}
}

func (d *ListUsersResultDto) GetUsers() []entities.UserPrimitives {
	return d.users
}

func (d *ListUsersResultDto) ToArray() []entities.UserPrimitives {
	return d.users
}
