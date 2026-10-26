package gettask

import "task-webassembly/src/modules/shared/application"

// GetTaskResultDto resultado del caso de uso: solo primitivos (un objeto plano como lo da la API).
type GetTaskResultDto struct {
	task map[string]any
}

func GetTaskResultDtoFromPrimitives(primitives map[string]any) *GetTaskResultDto {
	return &GetTaskResultDto{
		task: application.ToMap(primitives["task"]),
	}
}

func (d *GetTaskResultDto) GetTask() map[string]any {
	return d.task
}
