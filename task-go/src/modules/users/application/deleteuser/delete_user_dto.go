package deleteuser

import "task-go/src/modules/shared/application"

type DeleteUserDto struct {
	application.AbstractInputDto
	authUserId   int
	authUserUuid string
	userUuid     string
}

func DeleteUserDtoFromPrimitives(primitives map[string]any) *DeleteUserDto {
	return &DeleteUserDto{
		AbstractInputDto: application.NewAbstractInputDto(primitives),
		authUserId:       application.GetInt(primitives, "authUserId"),
		authUserUuid:     application.GetTrimmedString(primitives, "authUserUuid"),
		userUuid:         application.GetTrimmedString(primitives, "userUuid"),
	}
}

func (d *DeleteUserDto) GetAuthUserId() int {
	return d.authUserId
}

func (d *DeleteUserDto) GetAuthUserUuid() string {
	return d.authUserUuid
}

func (d *DeleteUserDto) GetUserUuid() string {
	return d.userUuid
}
