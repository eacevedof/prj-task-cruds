package updatetask

import "task-webassembly/src/modules/shared/application"

// UpdateTaskResultDto resultado del caso de uso: solo primitivos (un objeto plano como lo da la API).
type UpdateTaskResultDto struct {
	task map[string]any
}

func UpdateTaskResultDtoFromPrimitives(primitives map[string]any) *UpdateTaskResultDto {
	return &UpdateTaskResultDto{
		task: application.ToMap(primitives["task"]),
	}
}

func (d *UpdateTaskResultDto) GetTask() map[string]any {
	return d.task
}
