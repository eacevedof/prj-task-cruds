package deletetask

import "task-go/src/modules/shared/application"

// DeleteTaskResultPrimitives tipo DeleteTaskResult de tasks.graphql.
type DeleteTaskResultPrimitives struct {
	TaskUuid string `json:"task_uuid"`
	Affected int    `json:"affected"`
}

type DeleteTaskResultDto struct {
	taskUuid string
	affected int
}

func DeleteTaskResultDtoFromPrimitives(primitives map[string]any) *DeleteTaskResultDto {
	return &DeleteTaskResultDto{
		taskUuid: application.GetTrimmedString(primitives, "taskUuid"),
		affected: application.GetInt(primitives, "affected"),
	}
}

func (d *DeleteTaskResultDto) GetTaskUuid() string {
	return d.taskUuid
}

func (d *DeleteTaskResultDto) GetAffected() int {
	return d.affected
}

func (d *DeleteTaskResultDto) ToArray() DeleteTaskResultPrimitives {
	return DeleteTaskResultPrimitives{TaskUuid: d.taskUuid, Affected: d.affected}
}
