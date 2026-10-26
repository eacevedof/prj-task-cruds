package createtask

import "task-go/src/modules/tasks/domain/entities"

type CreateTaskResultDto struct {
	// la task creada (TaskEntity.ToPrimitives)
	task entities.TaskPrimitives
}

func CreateTaskResultDtoFromPrimitives(primitives map[string]any) *CreateTaskResultDto {
	task, _ := primitives["task"].(entities.TaskPrimitives)
	return &CreateTaskResultDto{task: task}
}

func (d *CreateTaskResultDto) GetTask() entities.TaskPrimitives {
	return d.task
}

func (d *CreateTaskResultDto) ToArray() entities.TaskPrimitives {
	return d.task
}
