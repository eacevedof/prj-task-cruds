package createtask

import "task-webassembly/src/modules/shared/application"

// CreateTaskResultDto resultado del caso de uso: solo primitivos (un objeto plano como lo da la API).
type CreateTaskResultDto struct {
	task map[string]any
}

func CreateTaskResultDtoFromPrimitives(primitives map[string]any) *CreateTaskResultDto {
	return &CreateTaskResultDto{
		task: application.ToMap(primitives["task"]),
	}
}

func (d *CreateTaskResultDto) GetTask() map[string]any {
	return d.task
}
