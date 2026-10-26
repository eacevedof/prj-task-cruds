package updateuser

import "task-go/src/modules/users/domain/entities"

type UpdateUserResultDto struct {
	// el usuario modificado (UserEntity.ToPrimitives)
	user entities.UserPrimitives
}

func UpdateUserResultDtoFromPrimitives(primitives map[string]any) *UpdateUserResultDto {
	user, _ := primitives["user"].(entities.UserPrimitives)
	return &UpdateUserResultDto{user: user}
}

func (d *UpdateUserResultDto) GetUser() entities.UserPrimitives {
	return d.user
}

func (d *UpdateUserResultDto) ToArray() entities.UserPrimitives {
	return d.user
}
