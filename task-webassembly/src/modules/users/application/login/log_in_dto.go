package login

import "task-webassembly/src/modules/shared/application"

// LogInDto entrada del caso de uso: solo primitivos, todo string con trim (la API tambien recorta la password).
type LogInDto struct {
	userName string
	password string
}

func LogInDtoFromPrimitives(primitives map[string]any) *LogInDto {
	return &LogInDto{
		userName: application.GetTrimmedString(primitives, "userName"),
		password: application.GetTrimmedString(primitives, "password"),
	}
}

func (d *LogInDto) GetUserName() string {
	return d.userName
}

func (d *LogInDto) GetPassword() string {
	return d.password
}
