package deletetask

import (
	"task-webassembly/src/modules/shared/application"
	"task-webassembly/src/modules/tasks/domain/enums"
	"task-webassembly/src/modules/tasks/domain/exceptions"
	"task-webassembly/src/modules/tasks/infrastructure/repositories"
)

type DeleteTaskService struct {
	tasksWriterGraphqlRepository *repositories.TasksWriterGraphqlRepository
}

func DeleteTaskServiceGetInstance() *DeleteTaskService {
	return &DeleteTaskService{
		tasksWriterGraphqlRepository: repositories.TasksWriterGraphqlRepositoryGetInstance(),
	}
}

// Invoke error: *TasksException o uno inesperado.
func (s *DeleteTaskService) Invoke(deleteTaskDto *DeleteTaskDto) (*DeleteTaskResultDto, error) {
	apiResponse, err := s.tasksWriterGraphqlRepository.DeleteTaskByTaskUuid(
		deleteTaskDto.GetTaskUuid(),
		deleteTaskDto.GetApiToken(),
	)
	if err != nil {
		return nil, err
	}
	if !apiResponse.Status {
		return nil, exceptions.FromApiResponse(apiResponse)
	}

	return DeleteTaskResultDtoFromPrimitives(map[string]any{
		"taskUuid": application.ToMap(apiResponse.Data)[enums.TasksKeyEnum.TASK_UUID],
	}), nil
}
