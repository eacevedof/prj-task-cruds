package loginuser

import (
	"task-go/src/modules/shared/application"
	"task-go/src/modules/users/domain/entities"
)

type LogInUserResultDto struct {
	// el usuario (UserEntity.ToPrimitives) + su api_token nuevo en claro, solo aqui
	user     entities.UserPrimitives
	apiToken string
}

func LogInUserResultDtoFromPrimitives(primitives map[string]any) *LogInUserResultDto {
	user, _ := primitives["user"].(entities.UserPrimitives)
	return &LogInUserResultDto{
		user:     user,
		apiToken: application.GetTrimmedString(primitives, "apiToken"),
	}
}

func (d *LogInUserResultDto) GetUser() entities.UserPrimitives {
	return d.user
}

func (d *LogInUserResultDto) GetApiToken() string {
	return d.apiToken
}

func (d *LogInUserResultDto) ToArray() entities.UserWithApiTokenPrimitives {
	return entities.UserWithApiTokenPrimitives{UserPrimitives: d.user, ApiToken: d.apiToken}
}
