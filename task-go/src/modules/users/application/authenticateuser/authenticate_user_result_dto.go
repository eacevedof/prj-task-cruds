package authenticateuser

import "task-go/src/modules/shared/application"

// AuthenticateUserResultDto userId es el que usan todas las queries (mas ligero que el uuid); userUuid solo para
// comparar con el user_uuid que llega de fuera (argumento GraphQL, opcion de consola).
type AuthenticateUserResultDto struct {
	userId   int
	userUuid string
}

func AuthenticateUserResultDtoFromPrimitives(primitives map[string]any) *AuthenticateUserResultDto {
	return &AuthenticateUserResultDto{
		userId:   application.GetInt(primitives, "userId"),
		userUuid: application.GetTrimmedString(primitives, "userUuid"),
	}
}

func (d *AuthenticateUserResultDto) GetUserId() int {
	return d.userId
}

func (d *AuthenticateUserResultDto) GetUserUuid() string {
	return d.userUuid
}

func (d *AuthenticateUserResultDto) ToAuthPrimitives() map[string]any {
	return map[string]any{
		"authUserId":   d.userId,
		"authUserUuid": d.userUuid,
	}
}
