package deletetask

import "task-webassembly/src/modules/shared/application"

// DeleteTaskResultDto resultado del caso de uso: solo primitivos.
type DeleteTaskResultDto struct {
	taskUuid string
}

func DeleteTaskResultDtoFromPrimitives(primitives map[string]any) *DeleteTaskResultDto {
	return &DeleteTaskResultDto{
		taskUuid: application.GetTrimmedString(primitives, "taskUuid"),
	}
}

func (d *DeleteTaskResultDto) GetTaskUuid() string {
	return d.taskUuid
}
