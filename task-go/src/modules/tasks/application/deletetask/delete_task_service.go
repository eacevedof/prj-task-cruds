package deletetask

import (
	"task-go/src/core/config"
	"task-go/src/modules/tasks/domain/entities"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	"task-go/src/modules/tasks/infrastructure/repositories"
)

type DeleteTaskService struct {
	tasksReaderSqliteRepository *repositories.TasksReaderSqliteRepository
	tasksWriterSqliteRepository *repositories.TasksWriterSqliteRepository

	deleteTaskDto *DeleteTaskDto
}

func DeleteTaskServiceGetInstance() *DeleteTaskService {
	return &DeleteTaskService{
		tasksReaderSqliteRepository: repositories.TasksReaderSqliteRepositoryGetInstance(),
		tasksWriterSqliteRepository: repositories.TasksWriterSqliteRepositoryGetInstance(),
	}
}

// Invoke error: *TasksException (401, 404) o uno inesperado.
func (s *DeleteTaskService) Invoke(deleteTaskDto *DeleteTaskDto) (*DeleteTaskResultDto, error) {
	s.deleteTaskDto = deleteTaskDto
	if err := s.failIfWrongInput(); err != nil {
		return nil, err
	}

	taskEntity, err := s.tryToGetOwnTaskOrFail()
	if err != nil {
		return nil, err
	}

	affected, err := s.tasksWriterSqliteRepository.DeleteTaskById(taskEntity)
	if err != nil {
		return nil, err
	}
	return DeleteTaskResultDtoFromPrimitives(map[string]any{
		"taskUuid": taskEntity.GetTaskUuid(),
		"affected": affected,
	}), nil
}

func (s *DeleteTaskService) failIfWrongInput() error {
	if s.deleteTaskDto.GetAuthUserId() < 1 {
		return exceptions.UnauthorizedCustom(config.Tr(enums.TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN))
	}
	return nil
}

// tryToGetOwnTaskOrFail una task de otro usuario responde igual que si no existiera (no se revela que existe).
func (s *DeleteTaskService) tryToGetOwnTaskOrFail() (*entities.TaskEntity, error) {
	taskUuid := s.deleteTaskDto.GetTaskUuid()
	taskEntity, err := s.tasksReaderSqliteRepository.GetTaskByTaskUuid(taskUuid)
	if err != nil {
		return nil, err
	}
	if taskEntity == nil || taskEntity.GetUserId() != s.deleteTaskDto.GetAuthUserId() {
		return nil, exceptions.NotFoundCustom(config.Tr(enums.TasksMessageEnum.TASK_X_NOT_FOUND, taskUuid))
	}
	return taskEntity, nil
}
