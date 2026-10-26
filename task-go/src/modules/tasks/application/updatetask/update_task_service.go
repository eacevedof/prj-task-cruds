package updatetask

import (
	"task-go/src/core/config"
	sharedenums "task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/datetimer"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/shared/infrastructure/traits"
	"task-go/src/modules/tasks/domain/entities"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	"task-go/src/modules/tasks/domain/services"
	"task-go/src/modules/tasks/infrastructure/repositories"
)

// UpdateTaskService PUT: reemplazo completo (mismas reglas y defaults que el alta). El propietario no cambia.
type UpdateTaskService struct {
	traits.ErrorStackTrait

	dateTimer                   *datetimer.DateTimer
	tasksReaderSqliteRepository *repositories.TasksReaderSqliteRepository
	tasksWriterSqliteRepository *repositories.TasksWriterSqliteRepository

	updateTaskDto *UpdateTaskDto
	taskEntity    *entities.TaskEntity
}

func UpdateTaskServiceGetInstance() *UpdateTaskService {
	return &UpdateTaskService{
		dateTimer:                   datetimer.GetInstance(),
		tasksReaderSqliteRepository: repositories.TasksReaderSqliteRepositoryGetInstance(),
		tasksWriterSqliteRepository: repositories.TasksWriterSqliteRepositoryGetInstance(),
	}
}

// Invoke error: *FieldsException (400), *TasksException (401, 404) o uno inesperado.
func (s *UpdateTaskService) Invoke(updateTaskDto *UpdateTaskDto) (*UpdateTaskResultDto, error) {
	s.updateTaskDto = updateTaskDto
	if err := s.failIfWrongInput(); err != nil {
		return nil, err
	}

	currentTaskEntity, err := s.tryToGetOwnTaskOrFail()
	if err != nil {
		return nil, err
	}
	// reemplazo completo sobre la task actual: se conservan id, propietario y created_at
	s.taskEntity = entities.TaskEntityFromPrimitives(map[string]any{
		enums.TasksKeyEnum.ID:               currentTaskEntity.GetId(),
		enums.TasksKeyEnum.USER_ID:          currentTaskEntity.GetUserId(),
		enums.TasksKeyEnum.USER_UUID:        currentTaskEntity.GetUserUuid(),
		enums.TasksKeyEnum.TASK_UUID:        currentTaskEntity.GetTaskUuid(),
		enums.TasksKeyEnum.TASK_TITLE:       updateTaskDto.GetTaskTitle(),
		enums.TasksKeyEnum.TASK_STATUS:      getValueOrDefault(updateTaskDto.GetTaskStatus(), enums.TaskStatusEnum.DEFAULT),
		enums.TasksKeyEnum.TASK_TYPE:        getValueOrDefault(updateTaskDto.GetTaskType(), enums.TaskTypeEnum.DEFAULT),
		enums.TasksKeyEnum.TARGET_DATE:      updateTaskDto.GetTargetDate(),
		enums.TasksKeyEnum.TASK_DESCRIPTION: updateTaskDto.GetTaskDescription(),
		enums.TasksKeyEnum.CREATED_AT:       currentTaskEntity.GetCreatedAt(),
		enums.TasksKeyEnum.UPDATED_AT:       s.dateTimer.GetNowDtIntoTargetTz(),
	})

	if err := s.failIfWrongFields(); err != nil {
		return nil, err
	}

	if _, err := s.tasksWriterSqliteRepository.UpdateTaskById(s.taskEntity); err != nil {
		return nil, err
	}

	taskEntity, err := s.tasksReaderSqliteRepository.GetTaskById(s.taskEntity.GetId())
	if err != nil {
		return nil, err
	}
	return UpdateTaskResultDtoFromPrimitives(map[string]any{"task": entities.ToPrimitivesOrEmpty(taskEntity)}), nil
}

func (s *UpdateTaskService) failIfWrongInput() error {
	if s.updateTaskDto.GetAuthUserId() < 1 {
		return exceptions.UnauthorizedCustom(config.Tr(enums.TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN))
	}
	return nil
}

// tryToGetOwnTaskOrFail una task de otro usuario responde igual que si no existiera (no se revela que existe).
func (s *UpdateTaskService) tryToGetOwnTaskOrFail() (*entities.TaskEntity, error) {
	taskUuid := s.updateTaskDto.GetTaskUuid()
	taskEntity, err := s.tasksReaderSqliteRepository.GetTaskByTaskUuid(taskUuid)
	if err != nil {
		return nil, err
	}
	if taskEntity == nil || taskEntity.GetUserId() != s.updateTaskDto.GetAuthUserId() {
		return nil, exceptions.NotFoundCustom(config.Tr(enums.TasksMessageEnum.TASK_X_NOT_FOUND, taskUuid))
	}
	return taskEntity, nil
}

func (s *UpdateTaskService) failIfWrongFields() error {
	if errors := services.TaskFieldsValidatorGetInstance().GetErrors(s.taskEntity); len(errors) > 0 {
		return sharedexceptions.FieldsExceptionBadRequestCustom(
			s.GetErrorStackMessage(
				config.Tr(sharedenums.SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors,
			))
	}
	return nil
}

// getValueOrDefault el $valor ?: DEFAULT de task-php.
func getValueOrDefault(value string, defaultValue string) string {
	if value == "" {
		return defaultValue
	}
	return value
}
