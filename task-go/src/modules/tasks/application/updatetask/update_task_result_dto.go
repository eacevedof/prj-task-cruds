package updatetask

import "task-go/src/modules/tasks/domain/entities"

type UpdateTaskResultDto struct {
	// la task modificada (TaskEntity.ToPrimitives)
	task entities.TaskPrimitives
}

func UpdateTaskResultDtoFromPrimitives(primitives map[string]any) *UpdateTaskResultDto {
	task, _ := primitives["task"].(entities.TaskPrimitives)
	return &UpdateTaskResultDto{task: task}
}

func (d *UpdateTaskResultDto) GetTask() entities.TaskPrimitives {
	return d.task
}

func (d *UpdateTaskResultDto) ToArray() entities.TaskPrimitives {
	return d.task
}
