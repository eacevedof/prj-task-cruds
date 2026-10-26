package listusers

import "task-go/src/modules/shared/application"

type ListUsersDto struct {
	application.AbstractInputDto
	authUserId   int
	authUserUuid string
}

func ListUsersDtoFromPrimitives(primitives map[string]any) *ListUsersDto {
	return &ListUsersDto{
		AbstractInputDto: application.NewAbstractInputDto(primitives),
		authUserId:       application.GetInt(primitives, "authUserId"),
		authUserUuid:     application.GetTrimmedString(primitives, "authUserUuid"),
	}
}

func (d *ListUsersDto) GetAuthUserId() int {
	return d.authUserId
}

func (d *ListUsersDto) GetAuthUserUuid() string {
	return d.authUserUuid
}
