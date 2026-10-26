package gettask

import "task-go/src/modules/tasks/domain/entities"

type GetTaskResultDto struct {
	// la task (TaskEntity.ToPrimitives)
	task entities.TaskPrimitives
}

func GetTaskResultDtoFromPrimitives(primitives map[string]any) *GetTaskResultDto {
	task, _ := primitives["task"].(entities.TaskPrimitives)
	return &GetTaskResultDto{task: task}
}

func (d *GetTaskResultDto) GetTask() entities.TaskPrimitives {
	return d.task
}

func (d *GetTaskResultDto) ToArray() entities.TaskPrimitives {
	return d.task
}
