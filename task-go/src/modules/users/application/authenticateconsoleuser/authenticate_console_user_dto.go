package authenticateconsoleuser

import "task-go/src/modules/shared/application"

type AuthenticateConsoleUserDto struct {
	application.AbstractInputDto
	userUuid string
}

func AuthenticateConsoleUserDtoFromPrimitives(primitives map[string]any) *AuthenticateConsoleUserDto {
	return &AuthenticateConsoleUserDto{
		AbstractInputDto: application.NewAbstractInputDto(primitives),
		userUuid:         application.GetTrimmedString(primitives, "userUuid"),
	}
}

func (d *AuthenticateConsoleUserDto) GetUserUuid() string {
	return d.userUuid
}
