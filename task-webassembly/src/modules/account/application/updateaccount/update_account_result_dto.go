package updateaccount

import "task-webassembly/src/modules/shared/application"

// UpdateAccountResultDto resultado del caso de uso: solo primitivos.
type UpdateAccountResultDto struct {
	userUuid  string
	userName  string
	userEmail string
}

func UpdateAccountResultDtoFromPrimitives(primitives map[string]any) *UpdateAccountResultDto {
	return &UpdateAccountResultDto{
		userUuid:  application.GetTrimmedString(primitives, "userUuid"),
		userName:  application.GetTrimmedString(primitives, "userName"),
		userEmail: application.GetTrimmedString(primitives, "userEmail"),
	}
}

func (d *UpdateAccountResultDto) GetUserUuid() string {
	return d.userUuid
}

func (d *UpdateAccountResultDto) GetUserName() string {
	return d.userName
}

func (d *UpdateAccountResultDto) GetUserEmail() string {
	return d.userEmail
}
