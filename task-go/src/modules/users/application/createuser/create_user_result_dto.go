package createuser

import (
	"task-go/src/modules/shared/application"
	"task-go/src/modules/users/domain/entities"
)

type CreateUserResultDto struct {
	// el usuario creado (UserEntity.ToPrimitives) + su api_token en claro, solo aqui
	user     entities.UserPrimitives
	apiToken string
}

func CreateUserResultDtoFromPrimitives(primitives map[string]any) *CreateUserResultDto {
	user, _ := primitives["user"].(entities.UserPrimitives)
	return &CreateUserResultDto{
		user:     user,
		apiToken: application.GetTrimmedString(primitives, "apiToken"),
	}
}

func (d *CreateUserResultDto) GetUser() entities.UserPrimitives {
	return d.user
}

func (d *CreateUserResultDto) GetApiToken() string {
	return d.apiToken
}

func (d *CreateUserResultDto) ToArray() entities.UserWithApiTokenPrimitives {
	return entities.UserWithApiTokenPrimitives{UserPrimitives: d.user, ApiToken: d.apiToken}
}
