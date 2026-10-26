package createuser

import "task-webassembly/src/modules/shared/application"

// CreateUserDto entrada del caso de uso: solo primitivos, todo string con trim.
type CreateUserDto struct {
	userName  string
	userEmail string
	password  string
}

func CreateUserDtoFromPrimitives(primitives map[string]any) *CreateUserDto {
	return &CreateUserDto{
		userName:  application.GetTrimmedString(primitives, "userName"),
		userEmail: application.GetTrimmedString(primitives, "userEmail"),
		password:  application.GetTrimmedString(primitives, "password"),
	}
}

func (d *CreateUserDto) GetUserName() string {
	return d.userName
}

func (d *CreateUserDto) GetUserEmail() string {
	return d.userEmail
}

func (d *CreateUserDto) GetPassword() string {
	return d.password
}
