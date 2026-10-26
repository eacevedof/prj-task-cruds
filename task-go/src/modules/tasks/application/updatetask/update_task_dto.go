package updatetask

import "task-go/src/modules/shared/application"

type UpdateTaskDto struct {
	application.AbstractInputDto
	authUserId      int
	authUserUuid    string
	taskUuid        string
	taskTitle       string
	taskStatus      string
	taskType        string
	targetDate      string
	taskDescription string
}

func UpdateTaskDtoFromPrimitives(primitives map[string]any) *UpdateTaskDto {
	return &UpdateTaskDto{
		AbstractInputDto: application.NewAbstractInputDto(primitives),
		authUserId:       application.GetInt(primitives, "authUserId"),
		authUserUuid:     application.GetTrimmedString(primitives, "authUserUuid"),
		taskUuid:         application.GetTrimmedString(primitives, "taskUuid"),
		taskTitle:        application.GetTrimmedString(primitives, "taskTitle"),
		taskStatus:       application.GetTrimmedString(primitives, "taskStatus"),
		taskType:         application.GetTrimmedString(primitives, "taskType"),
		targetDate:       application.GetTrimmedString(primitives, "targetDate"),
		taskDescription:  application.GetTrimmedString(primitives, "taskDescription"),
	}
}

func (d *UpdateTaskDto) GetAuthUserId() int {
	return d.authUserId
}

func (d *UpdateTaskDto) GetAuthUserUuid() string {
	return d.authUserUuid
}

func (d *UpdateTaskDto) GetTaskUuid() string {
	return d.taskUuid
}

func (d *UpdateTaskDto) GetTaskTitle() string {
	return d.taskTitle
}

func (d *UpdateTaskDto) GetTaskStatus() string {
	return d.taskStatus
}

func (d *UpdateTaskDto) GetTaskType() string {
	return d.taskType
}

func (d *UpdateTaskDto) GetTargetDate() string {
	return d.targetDate
}

func (d *UpdateTaskDto) GetTaskDescription() string {
	return d.taskDescription
}
