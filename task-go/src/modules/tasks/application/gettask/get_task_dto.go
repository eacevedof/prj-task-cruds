package gettask

import "task-go/src/modules/shared/application"

type GetTaskDto struct {
	application.AbstractInputDto
	authUserId   int
	authUserUuid string
	taskUuid     string
}

func GetTaskDtoFromPrimitives(primitives map[string]any) *GetTaskDto {
	return &GetTaskDto{
		AbstractInputDto: application.NewAbstractInputDto(primitives),
		authUserId:       application.GetInt(primitives, "authUserId"),
		authUserUuid:     application.GetTrimmedString(primitives, "authUserUuid"),
		taskUuid:         application.GetTrimmedString(primitives, "taskUuid"),
	}
}

func (d *GetTaskDto) GetAuthUserId() int {
	return d.authUserId
}

func (d *GetTaskDto) GetAuthUserUuid() string {
	return d.authUserUuid
}

func (d *GetTaskDto) GetTaskUuid() string {
	return d.taskUuid
}
