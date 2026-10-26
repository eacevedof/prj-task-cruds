package updatetask

import (
	"task-webassembly/src/modules/tasks/domain/enums"
	"task-webassembly/src/modules/tasks/domain/exceptions"
	"task-webassembly/src/modules/tasks/infrastructure/repositories"
)

// UpdateTaskService updateTask: reemplazo completo de la tarea (la vista siempre envia el formulario entero).
type UpdateTaskService struct {
	tasksWriterGraphqlRepository *repositories.TasksWriterGraphqlRepository
}

func UpdateTaskServiceGetInstance() *UpdateTaskService {
	return &UpdateTaskService{
		tasksWriterGraphqlRepository: repositories.TasksWriterGraphqlRepositoryGetInstance(),
	}
}

// Invoke error: *TasksException o uno inesperado.
func (s *UpdateTaskService) Invoke(updateTaskDto *UpdateTaskDto) (*UpdateTaskResultDto, error) {
	// en la interfaz la fecha objetivo es obligatoria (la API la admite vacia)
	errors := []string{}
	if updateTaskDto.GetTaskTitle() == "" {
		errors = append(errors, enums.TasksMessageEnum.TASK_TITLE_IS_REQUIRED)
	}
	if updateTaskDto.GetTargetDate() == "" {
		errors = append(errors, enums.TasksMessageEnum.TARGET_DATE_IS_REQUIRED)
	}
	if len(errors) > 0 {
		return nil, exceptions.BadRequestCustom(errors)
	}

	apiResponse, err := s.tasksWriterGraphqlRepository.UpdateTaskByTaskUuid(
		updateTaskDto.GetTaskUuid(),
		map[string]any{
			"taskTitle":       updateTaskDto.GetTaskTitle(),
			"taskStatus":      updateTaskDto.GetTaskStatus(),
			"taskType":        updateTaskDto.GetTaskType(),
			"targetDate":      updateTaskDto.GetTargetDate(),
			"taskDescription": updateTaskDto.GetTaskDescription(),
		},
		updateTaskDto.GetApiToken(),
	)
	if err != nil {
		return nil, err
	}
	if !apiResponse.Status {
		return nil, exceptions.FromApiResponse(apiResponse)
	}

	return UpdateTaskResultDtoFromPrimitives(map[string]any{
		"task": apiResponse.Data,
	}), nil
}
