package entities

import (
	"task-go/src/modules/shared/application"
	"task-go/src/modules/tasks/domain/enums"
)

const TASK_UUID_PREFIX = "task-"

// TaskPrimitives representacion publica (API): el identificador para operar es task_uuid; task_id es solo
// informativo (orden, referencia legible). El propietario va por user_uuid, nunca por su id interno.
// Los opcionales vacios salen como null (contrato de la API).
type TaskPrimitives struct {
	TaskId          int     `json:"task_id"`
	TaskUuid        string  `json:"task_uuid"`
	UserUuid        string  `json:"user_uuid"`
	TaskTitle       string  `json:"task_title"`
	TaskStatus      string  `json:"task_status"`
	TaskType        string  `json:"task_type"`
	TargetDate      *string `json:"target_date"`
	TaskDescription *string `json:"task_description"`
	CreatedAt       string  `json:"created_at"`
	UpdatedAt       string  `json:"updated_at"`
}

type TaskEntity struct {
	id              int
	userId          int
	userUuid        string
	taskUuid        string
	taskTitle       string
	taskStatus      string
	taskType        string
	targetDate      string
	taskDescription string
	createdAt       string
	updatedAt       string
}

// TaskEntityFromPrimitives desde una fila de tasks JOIN users (user_uuid viene del usuario propietario; columnas en
// snake_case: contrato con la BD). target_date / task_description NULL en BD -> "" aqui (neutro de string).
func TaskEntityFromPrimitives(primitives map[string]any) *TaskEntity {
	return &TaskEntity{
		id:              application.GetInt(primitives, enums.TasksKeyEnum.ID),
		userId:          application.GetInt(primitives, enums.TasksKeyEnum.USER_ID),
		userUuid:        application.GetTrimmedString(primitives, enums.TasksKeyEnum.USER_UUID),
		taskUuid:        application.GetTrimmedString(primitives, enums.TasksKeyEnum.TASK_UUID),
		taskTitle:       application.GetTrimmedString(primitives, enums.TasksKeyEnum.TASK_TITLE),
		taskStatus:      application.GetTrimmedString(primitives, enums.TasksKeyEnum.TASK_STATUS),
		taskType:        application.GetTrimmedString(primitives, enums.TasksKeyEnum.TASK_TYPE),
		targetDate:      application.GetTrimmedString(primitives, enums.TasksKeyEnum.TARGET_DATE),
		taskDescription: application.GetTrimmedString(primitives, enums.TasksKeyEnum.TASK_DESCRIPTION),
		createdAt:       application.GetTrimmedString(primitives, enums.TasksKeyEnum.CREATED_AT),
		updatedAt:       application.GetTrimmedString(primitives, enums.TasksKeyEnum.UPDATED_AT),
	}
}

func (e *TaskEntity) ToPrimitives() TaskPrimitives {
	return TaskPrimitives{
		TaskId:          e.id,
		TaskUuid:        e.taskUuid,
		UserUuid:        e.userUuid,
		TaskTitle:       e.taskTitle,
		TaskStatus:      e.taskStatus,
		TaskType:        e.taskType,
		TargetDate:      getNullIfEmpty(e.targetDate),
		TaskDescription: getNullIfEmpty(e.taskDescription),
		CreatedAt:       e.createdAt,
		UpdatedAt:       e.updatedAt,
	}
}

// ToPrimitivesOrEmpty el ?->toPrimitives() ?? [] de task-php: una lectura que no encuentra nada da la task vacia.
func ToPrimitivesOrEmpty(taskEntity *TaskEntity) TaskPrimitives {
	if taskEntity == nil {
		return TaskPrimitives{}
	}
	return taskEntity.ToPrimitives()
}

func (e *TaskEntity) GetId() int {
	return e.id
}

func (e *TaskEntity) GetUserId() int {
	return e.userId
}

func (e *TaskEntity) GetUserUuid() string {
	return e.userUuid
}

func (e *TaskEntity) GetTaskUuid() string {
	return e.taskUuid
}

func (e *TaskEntity) GetTaskTitle() string {
	return e.taskTitle
}

func (e *TaskEntity) GetTaskStatus() string {
	return e.taskStatus
}

func (e *TaskEntity) GetTaskType() string {
	return e.taskType
}

func (e *TaskEntity) GetTargetDate() string {
	return e.targetDate
}

func (e *TaskEntity) GetTaskDescription() string {
	return e.taskDescription
}

func (e *TaskEntity) GetCreatedAt() string {
	return e.createdAt
}

func (e *TaskEntity) GetUpdatedAt() string {
	return e.updatedAt
}

func getNullIfEmpty(value string) *string {
	if value == "" {
		return nil
	}
	return &value
}
