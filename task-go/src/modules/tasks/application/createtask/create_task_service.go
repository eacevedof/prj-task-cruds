package createtask

import (
	"task-go/src/core/config"
	sharedenums "task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/datetimer"
	"task-go/src/modules/shared/infrastructure/components/uuid"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/shared/infrastructure/traits"
	"task-go/src/modules/tasks/domain/entities"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	"task-go/src/modules/tasks/domain/services"
	"task-go/src/modules/tasks/infrastructure/repositories"
)

// CreateTaskService el dueño es siempre el usuario autenticado. task_status / task_type vacios -> su DEFAULT.
type CreateTaskService struct {
	traits.ErrorStackTrait

	dateTimer                   *datetimer.DateTimer
	tasksReaderSqliteRepository *repositories.TasksReaderSqliteRepository
	tasksWriterSqliteRepository *repositories.TasksWriterSqliteRepository

	createTaskDto *CreateTaskDto
	taskEntity    *entities.TaskEntity
}

func CreateTaskServiceGetInstance() *CreateTaskService {
	return &CreateTaskService{
		dateTimer:                   datetimer.GetInstance(),
		tasksReaderSqliteRepository: repositories.TasksReaderSqliteRepositoryGetInstance(),
		tasksWriterSqliteRepository: repositories.TasksWriterSqliteRepositoryGetInstance(),
	}
}

// Invoke error: *FieldsException (400), *TasksException (401) o uno inesperado.
func (s *CreateTaskService) Invoke(createTaskDto *CreateTaskDto) (*CreateTaskResultDto, error) {
	s.createTaskDto = createTaskDto
	if err := s.failIfWrongInput(); err != nil {
		return nil, err
	}

	s.taskEntity = entities.TaskEntityFromPrimitives(map[string]any{
		enums.TasksKeyEnum.USER_ID:          createTaskDto.GetAuthUserId(),
		enums.TasksKeyEnum.USER_UUID:        createTaskDto.GetAuthUserUuid(),
		enums.TasksKeyEnum.TASK_UUID:        uuid.GetInstance().GetUniqueIdWithPrefix(entities.TASK_UUID_PREFIX),
		enums.TasksKeyEnum.TASK_TITLE:       createTaskDto.GetTaskTitle(),
		enums.TasksKeyEnum.TASK_STATUS:      getValueOrDefault(createTaskDto.GetTaskStatus(), enums.TaskStatusEnum.DEFAULT),
		enums.TasksKeyEnum.TASK_TYPE:        getValueOrDefault(createTaskDto.GetTaskType(), enums.TaskTypeEnum.DEFAULT),
		enums.TasksKeyEnum.TARGET_DATE:      createTaskDto.GetTargetDate(),
		enums.TasksKeyEnum.TASK_DESCRIPTION: createTaskDto.GetTaskDescription(),
		enums.TasksKeyEnum.CREATED_AT:       s.dateTimer.GetNowDtIntoTargetTz(),
	})

	if err := s.failIfWrongFields(); err != nil {
		return nil, err
	}

	taskId, err := s.tasksWriterSqliteRepository.InsertTask(s.taskEntity)
	if err != nil {
		return nil, err
	}

	taskEntity, err := s.tasksReaderSqliteRepository.GetTaskById(taskId)
	if err != nil {
		return nil, err
	}
	return CreateTaskResultDtoFromPrimitives(map[string]any{"task": entities.ToPrimitivesOrEmpty(taskEntity)}), nil
}

// failIfWrongInput por API nunca llega sin usuario (el controller corta con 401); protege otras entradas (consola,
// tests).
func (s *CreateTaskService) failIfWrongInput() error {
	if s.createTaskDto.GetAuthUserId() < 1 {
		return exceptions.UnauthorizedCustom(config.Tr(enums.TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN))
	}
	return nil
}

func (s *CreateTaskService) failIfWrongFields() error {
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
