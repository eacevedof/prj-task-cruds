package restoresession

import "task-webassembly/src/modules/shared/application"

// RestoreSessionDto entrada del caso de uso: solo primitivos, todo string con trim.
type RestoreSessionDto struct {
	apiToken string
}

func RestoreSessionDtoFromPrimitives(primitives map[string]any) *RestoreSessionDto {
	return &RestoreSessionDto{
		apiToken: application.GetTrimmedString(primitives, "apiToken"),
	}
}

func (d *RestoreSessionDto) GetApiToken() string {
	return d.apiToken
}
