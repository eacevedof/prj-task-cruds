package gettask

import "task-webassembly/src/modules/shared/application"

// GetTaskDto entrada del caso de uso: solo primitivos, todo string con trim.
type GetTaskDto struct {
	apiToken string
	taskUuid string
}

func GetTaskDtoFromPrimitives(primitives map[string]any) *GetTaskDto {
	return &GetTaskDto{
		apiToken: application.GetTrimmedString(primitives, "apiToken"),
		taskUuid: application.GetTrimmedString(primitives, "taskUuid"),
	}
}

func (d *GetTaskDto) GetApiToken() string {
	return d.apiToken
}

func (d *GetTaskDto) GetTaskUuid() string {
	return d.taskUuid
}
