package searchtasks

import (
	"task-go/src/modules/shared/application"
	"task-go/src/modules/tasks/domain/services"
)

type SearchTasksDto struct {
	application.AbstractInputDto
	authUserId     int
	authUserUuid   string
	userUuid       string
	taskText       string
	taskStatus     string
	taskType       string
	targetDateFrom string
	targetDateTo   string
	page           int
	pageSize       int
}

// SearchTasksDtoFromPrimitives userUuid vacio = las del usuario autenticado. Sin page/pageSize: primera pagina con el
// tamaño por defecto.
func SearchTasksDtoFromPrimitives(primitives map[string]any) *SearchTasksDto {
	return &SearchTasksDto{
		AbstractInputDto: application.NewAbstractInputDto(primitives),
		authUserId:       application.GetInt(primitives, "authUserId"),
		authUserUuid:     application.GetTrimmedString(primitives, "authUserUuid"),
		userUuid:         application.GetTrimmedString(primitives, "userUuid"),
		taskText:         application.GetTrimmedString(primitives, "taskText"),
		taskStatus:       application.GetTrimmedString(primitives, "taskStatus"),
		taskType:         application.GetTrimmedString(primitives, "taskType"),
		targetDateFrom:   application.GetTrimmedString(primitives, "targetDateFrom"),
		targetDateTo:     application.GetTrimmedString(primitives, "targetDateTo"),
		page:             application.GetIntOrDefault(primitives, "page", services.PAGE_MIN),
		pageSize:         application.GetIntOrDefault(primitives, "pageSize", services.PAGE_SIZE_DEFAULT),
	}
}

func (d *SearchTasksDto) GetAuthUserId() int {
	return d.authUserId
}

func (d *SearchTasksDto) GetAuthUserUuid() string {
	return d.authUserUuid
}

func (d *SearchTasksDto) GetUserUuid() string {
	return d.userUuid
}

func (d *SearchTasksDto) GetTaskText() string {
	return d.taskText
}

func (d *SearchTasksDto) GetTaskStatus() string {
	return d.taskStatus
}

func (d *SearchTasksDto) GetTaskType() string {
	return d.taskType
}

func (d *SearchTasksDto) GetTargetDateFrom() string {
	return d.targetDateFrom
}

func (d *SearchTasksDto) GetTargetDateTo() string {
	return d.targetDateTo
}

func (d *SearchTasksDto) GetPage() int {
	return d.page
}

func (d *SearchTasksDto) GetPageSize() int {
	return d.pageSize
}
