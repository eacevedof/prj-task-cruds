package createuser

import "task-webassembly/src/modules/shared/application"

// CreateUserResultDto resultado del caso de uso: solo primitivos.
type CreateUserResultDto struct {
	apiToken  string
	userUuid  string
	userName  string
	userEmail string
}

func CreateUserResultDtoFromPrimitives(primitives map[string]any) *CreateUserResultDto {
	return &CreateUserResultDto{
		apiToken:  application.GetTrimmedString(primitives, "apiToken"),
		userUuid:  application.GetTrimmedString(primitives, "userUuid"),
		userName:  application.GetTrimmedString(primitives, "userName"),
		userEmail: application.GetTrimmedString(primitives, "userEmail"),
	}
}

func (d *CreateUserResultDto) GetApiToken() string {
	return d.apiToken
}

func (d *CreateUserResultDto) GetUserUuid() string {
	return d.userUuid
}

func (d *CreateUserResultDto) GetUserName() string {
	return d.userName
}

func (d *CreateUserResultDto) GetUserEmail() string {
	return d.userEmail
}
