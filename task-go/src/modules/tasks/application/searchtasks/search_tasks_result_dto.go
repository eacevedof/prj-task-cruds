package searchtasks

import (
	"task-go/src/modules/shared/application"
	"task-go/src/modules/tasks/domain/entities"
)

// SearchTasksResultPrimitives tipo TaskPage de schema.graphql.
type SearchTasksResultPrimitives struct {
	Items      []entities.TaskPrimitives `json:"items"`
	Page       int                       `json:"page"`
	PageSize   int                       `json:"page_size"`
	TotalItems int                       `json:"total_items"`
	TotalPages int                       `json:"total_pages"`
}

type SearchTasksResultDto struct {
	// la pagina pedida: lista de TaskEntity.ToPrimitives
	tasks      []entities.TaskPrimitives
	page       int
	pageSize   int
	totalItems int
	totalPages int
}

func SearchTasksResultDtoFromPrimitives(primitives map[string]any) *SearchTasksResultDto {
	tasks, _ := primitives["tasks"].([]entities.TaskPrimitives)
	return &SearchTasksResultDto{
		tasks:      tasks,
		page:       application.GetInt(primitives, "page"),
		pageSize:   application.GetInt(primitives, "pageSize"),
		totalItems: application.GetInt(primitives, "totalItems"),
		totalPages: application.GetInt(primitives, "totalPages"),
	}
}

func (d *SearchTasksResultDto) GetTasks() []entities.TaskPrimitives {
	return d.tasks
}

func (d *SearchTasksResultDto) GetPage() int {
	return d.page
}

func (d *SearchTasksResultDto) GetPageSize() int {
	return d.pageSize
}

func (d *SearchTasksResultDto) GetTotalItems() int {
	return d.totalItems
}

func (d *SearchTasksResultDto) GetTotalPages() int {
	return d.totalPages
}

// ToArray tipo TaskPage de schema.graphql.
func (d *SearchTasksResultDto) ToArray() SearchTasksResultPrimitives {
	return SearchTasksResultPrimitives{
		Items:      d.tasks,
		Page:       d.page,
		PageSize:   d.pageSize,
		TotalItems: d.totalItems,
		TotalPages: d.totalPages,
	}
}
