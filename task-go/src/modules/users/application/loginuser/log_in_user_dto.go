package loginuser

import "task-go/src/modules/shared/application"

type LogInUserDto struct {
	application.AbstractInputDto
	userName string
	password string
}

func LogInUserDtoFromPrimitives(primitives map[string]any) *LogInUserDto {
	return &LogInUserDto{
		AbstractInputDto: application.NewAbstractInputDto(primitives),
		userName:         application.GetTrimmedString(primitives, "userName"),
		password:         application.GetTrimmedString(primitives, "password"),
	}
}

func (d *LogInUserDto) GetUserName() string {
	return d.userName
}

func (d *LogInUserDto) GetPassword() string {
	return d.password
}
