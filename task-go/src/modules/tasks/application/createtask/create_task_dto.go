package createtask

import "task-go/src/modules/shared/application"

type CreateTaskDto struct {
	application.AbstractInputDto
	authUserId      int
	authUserUuid    string
	taskTitle       string
	taskStatus      string
	taskType        string
	targetDate      string
	taskDescription string
}

func CreateTaskDtoFromPrimitives(primitives map[string]any) *CreateTaskDto {
	return &CreateTaskDto{
		AbstractInputDto: application.NewAbstractInputDto(primitives),
		authUserId:       application.GetInt(primitives, "authUserId"),
		authUserUuid:     application.GetTrimmedString(primitives, "authUserUuid"),
		taskTitle:        application.GetTrimmedString(primitives, "taskTitle"),
		taskStatus:       application.GetTrimmedString(primitives, "taskStatus"),
		taskType:         application.GetTrimmedString(primitives, "taskType"),
		targetDate:       application.GetTrimmedString(primitives, "targetDate"),
		taskDescription:  application.GetTrimmedString(primitives, "taskDescription"),
	}
}

func (d *CreateTaskDto) GetAuthUserId() int {
	return d.authUserId
}

func (d *CreateTaskDto) GetAuthUserUuid() string {
	return d.authUserUuid
}

func (d *CreateTaskDto) GetTaskTitle() string {
	return d.taskTitle
}

func (d *CreateTaskDto) GetTaskStatus() string {
	return d.taskStatus
}

func (d *CreateTaskDto) GetTaskType() string {
	return d.taskType
}

func (d *CreateTaskDto) GetTargetDate() string {
	return d.targetDate
}

func (d *CreateTaskDto) GetTaskDescription() string {
	return d.taskDescription
}
