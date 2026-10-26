package listtasks

import (
	"task-webassembly/src/modules/shared/application"
	"task-webassembly/src/modules/tasks/domain/enums"
	"task-webassembly/src/modules/tasks/domain/exceptions"
	"task-webassembly/src/modules/tasks/infrastructure/repositories"
)

// ListTasksService una pagina de las tareas del usuario del token, filtrada por texto y rango de target_date. Filtrar,
// ordenar (id desc) y paginar lo hace la API (query tasks): aqui solo se pide y se da forma al resultado.
type ListTasksService struct {
	tasksReaderGraphqlRepository *repositories.TasksReaderGraphqlRepository
}

func ListTasksServiceGetInstance() *ListTasksService {
	return &ListTasksService{
		tasksReaderGraphqlRepository: repositories.TasksReaderGraphqlRepositoryGetInstance(),
	}
}

// Invoke error: *TasksException o uno inesperado.
func (s *ListTasksService) Invoke(listTasksDto *ListTasksDto) (*ListTasksResultDto, error) {
	apiResponse, err := s.tasksReaderGraphqlRepository.GetTasksPage(
		map[string]any{
			enums.TasksKeyEnum.TASK_TEXT:        listTasksDto.GetTaskText(),
			enums.TasksKeyEnum.TARGET_DATE_FROM: listTasksDto.GetTargetDateFrom(),
			enums.TasksKeyEnum.TARGET_DATE_TO:   listTasksDto.GetTargetDateTo(),
		},
		listTasksDto.GetPage(),
		listTasksDto.GetPageSize(),
		listTasksDto.GetApiToken(),
	)
	if err != nil {
		return nil, err
	}
	if !apiResponse.Status {
		return nil, exceptions.FromApiResponse(apiResponse)
	}

	taskPage := application.ToMap(apiResponse.Data)
	return ListTasksResultDtoFromPrimitives(map[string]any{
		"tasks":      taskPage[enums.TasksKeyEnum.ITEMS],
		"page":       taskPage[enums.TasksKeyEnum.PAGE],
		"pageSize":   taskPage[enums.TasksKeyEnum.PAGE_SIZE],
		"totalItems": taskPage[enums.TasksKeyEnum.TOTAL_ITEMS],
		"totalPages": taskPage[enums.TasksKeyEnum.TOTAL_PAGES],
	}), nil
}
