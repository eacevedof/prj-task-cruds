package deletetask

import "task-go/src/modules/shared/application"

type DeleteTaskDto struct {
	application.AbstractInputDto
	authUserId   int
	authUserUuid string
	taskUuid     string
}

func DeleteTaskDtoFromPrimitives(primitives map[string]any) *DeleteTaskDto {
	return &DeleteTaskDto{
		AbstractInputDto: application.NewAbstractInputDto(primitives),
		authUserId:       application.GetInt(primitives, "authUserId"),
		authUserUuid:     application.GetTrimmedString(primitives, "authUserUuid"),
		taskUuid:         application.GetTrimmedString(primitives, "taskUuid"),
	}
}

func (d *DeleteTaskDto) GetAuthUserId() int {
	return d.authUserId
}

func (d *DeleteTaskDto) GetAuthUserUuid() string {
	return d.authUserUuid
}

func (d *DeleteTaskDto) GetTaskUuid() string {
	return d.taskUuid
}
