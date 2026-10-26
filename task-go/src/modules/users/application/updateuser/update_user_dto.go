package updateuser

import "task-go/src/modules/shared/application"

type UpdateUserDto struct {
	application.AbstractInputDto
	authUserId   int
	authUserUuid string
	userUuid     string
	userName     string
	userEmail    string
}

func UpdateUserDtoFromPrimitives(primitives map[string]any) *UpdateUserDto {
	return &UpdateUserDto{
		AbstractInputDto: application.NewAbstractInputDto(primitives),
		authUserId:       application.GetInt(primitives, "authUserId"),
		authUserUuid:     application.GetTrimmedString(primitives, "authUserUuid"),
		userUuid:         application.GetTrimmedString(primitives, "userUuid"),
		userName:         application.GetTrimmedString(primitives, "userName"),
		userEmail:        application.GetTrimmedString(primitives, "userEmail"),
	}
}

func (d *UpdateUserDto) GetAuthUserId() int {
	return d.authUserId
}

func (d *UpdateUserDto) GetAuthUserUuid() string {
	return d.authUserUuid
}

func (d *UpdateUserDto) GetUserUuid() string {
	return d.userUuid
}

func (d *UpdateUserDto) GetUserName() string {
	return d.userName
}

func (d *UpdateUserDto) GetUserEmail() string {
	return d.userEmail
}
