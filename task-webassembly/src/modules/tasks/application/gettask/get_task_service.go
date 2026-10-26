package gettask

import (
	"task-webassembly/src/modules/tasks/domain/exceptions"
	"task-webassembly/src/modules/tasks/infrastructure/repositories"
)

// GetTaskService detalle completo de una tarea (para el modal de solo lectura): se pide a la API en el momento, no se
// reutiliza la fila de la lista, para ensenar el estado actual.
type GetTaskService struct {
	tasksReaderGraphqlRepository *repositories.TasksReaderGraphqlRepository
}

func GetTaskServiceGetInstance() *GetTaskService {
	return &GetTaskService{
		tasksReaderGraphqlRepository: repositories.TasksReaderGraphqlRepositoryGetInstance(),
	}
}

// Invoke error: *TasksException o uno inesperado.
func (s *GetTaskService) Invoke(getTaskDto *GetTaskDto) (*GetTaskResultDto, error) {
	apiResponse, err := s.tasksReaderGraphqlRepository.GetTaskByTaskUuid(
		getTaskDto.GetTaskUuid(),
		getTaskDto.GetApiToken(),
	)
	if err != nil {
		return nil, err
	}
	if !apiResponse.Status {
		return nil, exceptions.FromApiResponse(apiResponse)
	}

	return GetTaskResultDtoFromPrimitives(map[string]any{
		"task": apiResponse.Data,
	}), nil
}
