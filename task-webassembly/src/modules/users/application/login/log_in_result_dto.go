package login

import "task-webassembly/src/modules/shared/application"

// LogInResultDto resultado del caso de uso: solo primitivos.
type LogInResultDto struct {
	apiToken  string
	userUuid  string
	userName  string
	userEmail string
}

func LogInResultDtoFromPrimitives(primitives map[string]any) *LogInResultDto {
	return &LogInResultDto{
		apiToken:  application.GetTrimmedString(primitives, "apiToken"),
		userUuid:  application.GetTrimmedString(primitives, "userUuid"),
		userName:  application.GetTrimmedString(primitives, "userName"),
		userEmail: application.GetTrimmedString(primitives, "userEmail"),
	}
}

func (d *LogInResultDto) GetApiToken() string {
	return d.apiToken
}

func (d *LogInResultDto) GetUserUuid() string {
	return d.userUuid
}

func (d *LogInResultDto) GetUserName() string {
	return d.userName
}

func (d *LogInResultDto) GetUserEmail() string {
	return d.userEmail
}
