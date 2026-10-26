package updateaccount

import "task-webassembly/src/modules/shared/application"

// UpdateAccountDto entrada del caso de uso: solo primitivos, todo string con trim.
type UpdateAccountDto struct {
	apiToken  string
	userUuid  string
	userName  string
	userEmail string
}

func UpdateAccountDtoFromPrimitives(primitives map[string]any) *UpdateAccountDto {
	return &UpdateAccountDto{
		apiToken:  application.GetTrimmedString(primitives, "apiToken"),
		userUuid:  application.GetTrimmedString(primitives, "userUuid"),
		userName:  application.GetTrimmedString(primitives, "userName"),
		userEmail: application.GetTrimmedString(primitives, "userEmail"),
	}
}

func (d *UpdateAccountDto) GetApiToken() string {
	return d.apiToken
}

func (d *UpdateAccountDto) GetUserUuid() string {
	return d.userUuid
}

func (d *UpdateAccountDto) GetUserName() string {
	return d.userName
}

func (d *UpdateAccountDto) GetUserEmail() string {
	return d.userEmail
}
