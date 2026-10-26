package createuser

import "task-go/src/modules/shared/application"

type CreateUserDto struct {
	application.AbstractInputDto
	userName  string
	userEmail string
	password  string
}

func CreateUserDtoFromPrimitives(primitives map[string]any) *CreateUserDto {
	return &CreateUserDto{
		AbstractInputDto: application.NewAbstractInputDto(primitives),
		userName:         application.GetTrimmedString(primitives, "userName"),
		userEmail:        application.GetTrimmedString(primitives, "userEmail"),
		password:         application.GetTrimmedString(primitives, "password"),
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
