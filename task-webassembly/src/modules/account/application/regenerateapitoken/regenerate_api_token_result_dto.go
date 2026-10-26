package regenerateapitoken

import "task-webassembly/src/modules/shared/application"

// RegenerateApiTokenResultDto resultado del caso de uso: solo primitivos.
type RegenerateApiTokenResultDto struct {
	userUuid string
	apiToken string
}

func RegenerateApiTokenResultDtoFromPrimitives(primitives map[string]any) *RegenerateApiTokenResultDto {
	return &RegenerateApiTokenResultDto{
		userUuid: application.GetTrimmedString(primitives, "userUuid"),
		apiToken: application.GetTrimmedString(primitives, "apiToken"),
	}
}

func (d *RegenerateApiTokenResultDto) GetUserUuid() string {
	return d.userUuid
}

func (d *RegenerateApiTokenResultDto) GetApiToken() string {
	return d.apiToken
}
