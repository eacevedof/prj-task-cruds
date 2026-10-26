package listtasks

import (
	"task-webassembly/src/modules/shared/application"
	"task-webassembly/src/modules/tasks/domain/enums"
)

// ListTasksDto entrada del caso de uso: solo primitivos, todo string con trim.
type ListTasksDto struct {
	apiToken       string
	taskText       string
	targetDateFrom string
	targetDateTo   string
	page           int
	pageSize       int
}

func ListTasksDtoFromPrimitives(primitives map[string]any) *ListTasksDto {
	return &ListTasksDto{
		apiToken:       application.GetTrimmedString(primitives, "apiToken"),
		taskText:       application.GetTrimmedString(primitives, "taskText"),
		targetDateFrom: application.GetTrimmedString(primitives, "targetDateFrom"),
		targetDateTo:   application.GetTrimmedString(primitives, "targetDateTo"),
		page:           application.GetIntOrDefault(primitives, "page", enums.TasksPaginationEnum.FIRST_PAGE),
		pageSize:       application.GetIntOrDefault(primitives, "pageSize", enums.TasksPaginationEnum.PAGE_SIZE),
	}
}

func (d *ListTasksDto) GetApiToken() string {
	return d.apiToken
}

func (d *ListTasksDto) GetTaskText() string {
	return d.taskText
}

func (d *ListTasksDto) GetTargetDateFrom() string {
	return d.targetDateFrom
}

func (d *ListTasksDto) GetTargetDateTo() string {
	return d.targetDateTo
}

func (d *ListTasksDto) GetPage() int {
	return d.page
}

func (d *ListTasksDto) GetPageSize() int {
	return d.pageSize
}
