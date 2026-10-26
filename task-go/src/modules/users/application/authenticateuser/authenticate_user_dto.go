package authenticateuser

import "task-go/src/modules/shared/application"

type AuthenticateUserDto struct {
	application.AbstractInputDto
	apiToken string
}

func AuthenticateUserDtoFromPrimitives(primitives map[string]any) *AuthenticateUserDto {
	return &AuthenticateUserDto{
		AbstractInputDto: application.NewAbstractInputDto(primitives),
		apiToken:         application.GetTrimmedString(primitives, "apiToken"),
	}
}

func (d *AuthenticateUserDto) GetApiToken() string {
	return d.apiToken
}
