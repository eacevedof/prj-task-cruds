package regenerateapitoken

import "task-webassembly/src/modules/shared/application"

// RegenerateApiTokenDto entrada del caso de uso: solo primitivos, todo string con trim.
type RegenerateApiTokenDto struct {
	apiToken string
	userUuid string
}

func RegenerateApiTokenDtoFromPrimitives(primitives map[string]any) *RegenerateApiTokenDto {
	return &RegenerateApiTokenDto{
		apiToken: application.GetTrimmedString(primitives, "apiToken"),
		userUuid: application.GetTrimmedString(primitives, "userUuid"),
	}
}

func (d *RegenerateApiTokenDto) GetApiToken() string {
	return d.apiToken
}

func (d *RegenerateApiTokenDto) GetUserUuid() string {
	return d.userUuid
}
