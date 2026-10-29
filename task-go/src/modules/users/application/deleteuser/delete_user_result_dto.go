package deleteuser

import "task-go/src/modules/shared/application"

// DeleteUserResultPrimitives tipo DeleteUserResult de users.graphql.
type DeleteUserResultPrimitives struct {
	UserUuid string `json:"user_uuid"`
	Affected int    `json:"affected"`
}

type DeleteUserResultDto struct {
	userUuid string
	affected int
}

func DeleteUserResultDtoFromPrimitives(primitives map[string]any) *DeleteUserResultDto {
	return &DeleteUserResultDto{
		userUuid: application.GetTrimmedString(primitives, "userUuid"),
		affected: application.GetInt(primitives, "affected"),
	}
}

func (d *DeleteUserResultDto) GetUserUuid() string {
	return d.userUuid
}

func (d *DeleteUserResultDto) GetAffected() int {
	return d.affected
}

func (d *DeleteUserResultDto) ToArray() DeleteUserResultPrimitives {
	return DeleteUserResultPrimitives{UserUuid: d.userUuid, Affected: d.affected}
}
