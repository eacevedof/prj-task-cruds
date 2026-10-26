package getuser

import "task-go/src/modules/users/domain/entities"

type GetUserResultDto struct {
	// el usuario (UserEntity.ToPrimitives)
	user entities.UserPrimitives
}

func GetUserResultDtoFromPrimitives(primitives map[string]any) *GetUserResultDto {
	user, _ := primitives["user"].(entities.UserPrimitives)
	return &GetUserResultDto{user: user}
}

func (d *GetUserResultDto) GetUser() entities.UserPrimitives {
	return d.user
}

func (d *GetUserResultDto) ToArray() entities.UserPrimitives {
	return d.user
}
