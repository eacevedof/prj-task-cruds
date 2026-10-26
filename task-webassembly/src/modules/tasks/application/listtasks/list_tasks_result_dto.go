package listtasks

import "task-webassembly/src/modules/shared/application"

// ListTasksResultDto resultado del caso de uso: una pagina de tareas (objetos planos como los da la API) y su paginacion.
type ListTasksResultDto struct {
	tasks      []map[string]any
	page       int
	pageSize   int
	totalItems int
	totalPages int
}

func ListTasksResultDtoFromPrimitives(primitives map[string]any) *ListTasksResultDto {
	return &ListTasksResultDto{
		tasks:      application.ToMaps(primitives["tasks"]),
		page:       application.GetInt(primitives, "page"),
		pageSize:   application.GetInt(primitives, "pageSize"),
		totalItems: application.GetInt(primitives, "totalItems"),
		totalPages: application.GetInt(primitives, "totalPages"),
	}
}

// GetTasks las de la pagina pedida (id desc).
func (d *ListTasksResultDto) GetTasks() []map[string]any {
	return d.tasks
}

func (d *ListTasksResultDto) GetPage() int {
	return d.page
}

func (d *ListTasksResultDto) GetPageSize() int {
	return d.pageSize
}

// GetTotalItems las que cumplen los filtros, en todas las paginas.
func (d *ListTasksResultDto) GetTotalItems() int {
	return d.totalItems
}

func (d *ListTasksResultDto) GetTotalPages() int {
	return d.totalPages
}
