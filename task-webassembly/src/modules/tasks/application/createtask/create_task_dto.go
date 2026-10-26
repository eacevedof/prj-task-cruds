package createtask

import "task-webassembly/src/modules/shared/application"

// CreateTaskDto entrada del caso de uso: solo primitivos, todo string con trim.
type CreateTaskDto struct {
	apiToken        string
	taskTitle       string
	taskStatus      string
	taskType        string
	targetDate      string
	taskDescription string
}

func CreateTaskDtoFromPrimitives(primitives map[string]any) *CreateTaskDto {
	return &CreateTaskDto{
		apiToken:        application.GetTrimmedString(primitives, "apiToken"),
		taskTitle:       application.GetTrimmedString(primitives, "taskTitle"),
		taskStatus:      application.GetTrimmedString(primitives, "taskStatus"),
		taskType:        application.GetTrimmedString(primitives, "taskType"),
		targetDate:      application.GetTrimmedString(primitives, "targetDate"),
		taskDescription: application.GetTrimmedString(primitives, "taskDescription"),
	}
}

func (d *CreateTaskDto) GetApiToken() string {
	return d.apiToken
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
