package restoresession

import "task-webassembly/src/modules/shared/application"

// RestoreSessionResultDto resultado del caso de uso: solo primitivos.
type RestoreSessionResultDto struct {
	apiToken  string
	userUuid  string
	userName  string
	userEmail string
}

func RestoreSessionResultDtoFromPrimitives(primitives map[string]any) *RestoreSessionResultDto {
	return &RestoreSessionResultDto{
		apiToken:  application.GetTrimmedString(primitives, "apiToken"),
		userUuid:  application.GetTrimmedString(primitives, "userUuid"),
		userName:  application.GetTrimmedString(primitives, "userName"),
		userEmail: application.GetTrimmedString(primitives, "userEmail"),
	}
}

func (d *RestoreSessionResultDto) GetApiToken() string {
	return d.apiToken
}

func (d *RestoreSessionResultDto) GetUserUuid() string {
	return d.userUuid
}

func (d *RestoreSessionResultDto) GetUserName() string {
	return d.userName
}

func (d *RestoreSessionResultDto) GetUserEmail() string {
	return d.userEmail
}
