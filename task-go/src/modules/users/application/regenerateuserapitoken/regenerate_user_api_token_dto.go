package regenerateuserapitoken

import "task-go/src/modules/shared/application"

type RegenerateUserApiTokenDto struct {
	application.AbstractInputDto
	authUserId   int
	authUserUuid string
	userUuid     string
}

func RegenerateUserApiTokenDtoFromPrimitives(primitives map[string]any) *RegenerateUserApiTokenDto {
	return &RegenerateUserApiTokenDto{
		AbstractInputDto: application.NewAbstractInputDto(primitives),
		authUserId:       application.GetInt(primitives, "authUserId"),
		authUserUuid:     application.GetTrimmedString(primitives, "authUserUuid"),
		userUuid:         application.GetTrimmedString(primitives, "userUuid"),
	}
}

func (d *RegenerateUserApiTokenDto) GetAuthUserId() int {
	return d.authUserId
}

func (d *RegenerateUserApiTokenDto) GetAuthUserUuid() string {
	return d.authUserUuid
}

func (d *RegenerateUserApiTokenDto) GetUserUuid() string {
	return d.userUuid
}
