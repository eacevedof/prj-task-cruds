package searchtasks

import (
	"task-go/src/core/config"
	sharedenums "task-go/src/modules/shared/domain/enums"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/shared/infrastructure/traits"
	"task-go/src/modules/tasks/domain/entities"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	"task-go/src/modules/tasks/domain/services"
	"task-go/src/modules/tasks/infrastructure/repositories"
)

// SearchTasksService busqueda paginada de las tasks del usuario autenticado (mas reciente primero): filtros por
// texto, estado, tipo y rango de target_date.
type SearchTasksService struct {
	traits.ErrorStackTrait

	tasksSearchValidator        *services.TasksSearchValidator
	tasksReaderSqliteRepository *repositories.TasksReaderSqliteRepository

	searchTasksDto *SearchTasksDto
}

func SearchTasksServiceGetInstance() *SearchTasksService {
	return &SearchTasksService{
		tasksSearchValidator:        services.TasksSearchValidatorGetInstance(),
		tasksReaderSqliteRepository: repositories.TasksReaderSqliteRepositoryGetInstance(),
	}
}

// Invoke error: *FieldsException (400), *TasksException (401, 404) o uno inesperado.
func (s *SearchTasksService) Invoke(searchTasksDto *SearchTasksDto) (*SearchTasksResultDto, error) {
	s.searchTasksDto = searchTasksDto
	if err := s.failIfWrongInput(); err != nil {
		return nil, err
	}
	if err := s.failIfWrongSearch(); err != nil {
		return nil, err
	}

	userId := s.searchTasksDto.GetAuthUserId()

	totalItems, err := s.tasksReaderSqliteRepository.GetTasksCountByUserIdAndFilters(
		userId,
		s.searchTasksDto.GetTaskText(),
		s.searchTasksDto.GetTaskStatus(),
		s.searchTasksDto.GetTaskType(),
		s.searchTasksDto.GetTargetDateFrom(),
		s.searchTasksDto.GetTargetDateTo(),
	)
	if err != nil {
		return nil, err
	}
	page := s.searchTasksDto.GetPage()
	pageSize := s.searchTasksDto.GetPageSize()
	taskEntities, err := s.tasksReaderSqliteRepository.GetTasksByUserIdAndFilters(
		userId,
		s.searchTasksDto.GetTaskText(),
		s.searchTasksDto.GetTaskStatus(),
		s.searchTasksDto.GetTaskType(),
		s.searchTasksDto.GetTargetDateFrom(),
		s.searchTasksDto.GetTargetDateTo(),
		pageSize,
		(page-1)*pageSize,
	)
	if err != nil {
		return nil, err
	}

	tasks := []entities.TaskPrimitives{}
	for _, taskEntity := range taskEntities {
		tasks = append(tasks, taskEntity.ToPrimitives())
	}
	return SearchTasksResultDtoFromPrimitives(map[string]any{
		"tasks":      tasks,
		"page":       page,
		"pageSize":   pageSize,
		"totalItems": totalItems,
		// ceil(totalItems / pageSize) en enteros
		"totalPages": (totalItems + pageSize - 1) / pageSize,
	}), nil
}

// failIfWrongInput sin user_uuid se busca en las del autenticado; con uno ajeno responde igual que si no existiera.
func (s *SearchTasksService) failIfWrongInput() error {
	if s.searchTasksDto.GetAuthUserId() < 1 {
		return exceptions.UnauthorizedCustom(config.Tr(enums.TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN))
	}
	userUuid := s.searchTasksDto.GetUserUuid()
	if userUuid == "" {
		userUuid = s.searchTasksDto.GetAuthUserUuid()
	}
	if userUuid != s.searchTasksDto.GetAuthUserUuid() {
		return exceptions.NotFoundCustom(config.Tr(enums.TasksMessageEnum.USER_X_NOT_FOUND, userUuid))
	}
	return nil
}

func (s *SearchTasksService) failIfWrongSearch() error {
	errors := s.tasksSearchValidator.GetErrors(
		s.searchTasksDto.GetTaskStatus(),
		s.searchTasksDto.GetTaskType(),
		s.searchTasksDto.GetTargetDateFrom(),
		s.searchTasksDto.GetTargetDateTo(),
		s.searchTasksDto.GetPage(),
		s.searchTasksDto.GetPageSize(),
	)
	if len(errors) == 0 {
		return nil
	}

	return sharedexceptions.FieldsExceptionBadRequestCustom(
		s.GetErrorStackMessage(
			config.Tr(sharedenums.SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors,
		))
}
