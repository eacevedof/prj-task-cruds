package gettask

import (
	"task-go/src/core/config"
	"task-go/src/modules/tasks/domain/entities"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	"task-go/src/modules/tasks/infrastructure/repositories"
)

type GetTaskService struct {
	tasksReaderSqliteRepository *repositories.TasksReaderSqliteRepository

	getTaskDto *GetTaskDto
}

func GetTaskServiceGetInstance() *GetTaskService {
	return &GetTaskService{
		tasksReaderSqliteRepository: repositories.TasksReaderSqliteRepositoryGetInstance(),
	}
}

// Invoke error: *TasksException (401, 404) o uno inesperado.
func (s *GetTaskService) Invoke(getTaskDto *GetTaskDto) (*GetTaskResultDto, error) {
	s.getTaskDto = getTaskDto
	if err := s.failIfWrongInput(); err != nil {
		return nil, err
	}

	taskEntity, err := s.tryToGetOwnTaskOrFail()
	if err != nil {
		return nil, err
	}
	return GetTaskResultDtoFromPrimitives(map[string]any{"task": taskEntity.ToPrimitives()}), nil
}

func (s *GetTaskService) failIfWrongInput() error {
	if s.getTaskDto.GetAuthUserId() < 1 {
		return exceptions.UnauthorizedCustom(config.Tr(enums.TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN))
	}
	return nil
}

// tryToGetOwnTaskOrFail una task de otro usuario responde igual que si no existiera (no se revela que existe).
func (s *GetTaskService) tryToGetOwnTaskOrFail() (*entities.TaskEntity, error) {
	taskUuid := s.getTaskDto.GetTaskUuid()
	taskEntity, err := s.tasksReaderSqliteRepository.GetTaskByTaskUuid(taskUuid)
	if err != nil {
		return nil, err
	}
	if taskEntity == nil || taskEntity.GetUserId() != s.getTaskDto.GetAuthUserId() {
		return nil, exceptions.NotFoundCustom(config.Tr(enums.TasksMessageEnum.TASK_X_NOT_FOUND, taskUuid))
	}
	return taskEntity, nil
}
