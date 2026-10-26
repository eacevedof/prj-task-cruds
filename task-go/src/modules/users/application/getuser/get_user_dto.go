package getuser

import "task-go/src/modules/shared/application"

type GetUserDto struct {
	application.AbstractInputDto
	authUserId   int
	authUserUuid string
	userUuid     string
}

func GetUserDtoFromPrimitives(primitives map[string]any) *GetUserDto {
	return &GetUserDto{
		AbstractInputDto: application.NewAbstractInputDto(primitives),
		authUserId:       application.GetInt(primitives, "authUserId"),
		authUserUuid:     application.GetTrimmedString(primitives, "authUserUuid"),
		userUuid:         application.GetTrimmedString(primitives, "userUuid"),
	}
}

func (d *GetUserDto) GetAuthUserId() int {
	return d.authUserId
}

func (d *GetUserDto) GetAuthUserUuid() string {
	return d.authUserUuid
}

func (d *GetUserDto) GetUserUuid() string {
	return d.userUuid
}
