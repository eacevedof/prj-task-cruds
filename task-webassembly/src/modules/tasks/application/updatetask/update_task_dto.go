package updatetask

import "task-webassembly/src/modules/shared/application"

// UpdateTaskDto entrada del caso de uso: solo primitivos, todo string con trim.
type UpdateTaskDto struct {
	apiToken        string
	taskUuid        string
	taskTitle       string
	taskStatus      string
	taskType        string
	targetDate      string
	taskDescription string
}

func UpdateTaskDtoFromPrimitives(primitives map[string]any) *UpdateTaskDto {
	return &UpdateTaskDto{
		apiToken:        application.GetTrimmedString(primitives, "apiToken"),
		taskUuid:        application.GetTrimmedString(primitives, "taskUuid"),
		taskTitle:       application.GetTrimmedString(primitives, "taskTitle"),
		taskStatus:      application.GetTrimmedString(primitives, "taskStatus"),
		taskType:        application.GetTrimmedString(primitives, "taskType"),
		targetDate:      application.GetTrimmedString(primitives, "targetDate"),
		taskDescription: application.GetTrimmedString(primitives, "taskDescription"),
	}
}

func (d *UpdateTaskDto) GetApiToken() string {
	return d.apiToken
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
