package deletetask

import "task-webassembly/src/modules/shared/application"

// DeleteTaskDto entrada del caso de uso: solo primitivos, todo string con trim.
type DeleteTaskDto struct {
	apiToken string
	taskUuid string
}

func DeleteTaskDtoFromPrimitives(primitives map[string]any) *DeleteTaskDto {
	return &DeleteTaskDto{
		apiToken: application.GetTrimmedString(primitives, "apiToken"),
		taskUuid: application.GetTrimmedString(primitives, "taskUuid"),
	}
}

func (d *DeleteTaskDto) GetApiToken() string {
	return d.apiToken
}

func (d *DeleteTaskDto) GetTaskUuid() string {
	return d.taskUuid
}
