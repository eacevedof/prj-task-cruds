package createtask

import (
	"task-webassembly/src/modules/tasks/domain/enums"
	"task-webassembly/src/modules/tasks/domain/exceptions"
	"task-webassembly/src/modules/tasks/infrastructure/repositories"
)

// CreateTaskService alta de una tarea del usuario del token. Aqui se exigen titulo y fecha objetivo (feedback
// inmediato): estados, tipos, fecha y longitudes los valida la API, que es la que manda.
type CreateTaskService struct {
	tasksWriterGraphqlRepository *repositories.TasksWriterGraphqlRepository
}

func CreateTaskServiceGetInstance() *CreateTaskService {
	return &CreateTaskService{
		tasksWriterGraphqlRepository: repositories.TasksWriterGraphqlRepositoryGetInstance(),
	}
}

// Invoke error: *TasksException o uno inesperado (red, respuesta que no es JSON).
func (s *CreateTaskService) Invoke(createTaskDto *CreateTaskDto) (*CreateTaskResultDto, error) {
	// en la interfaz la fecha objetivo es obligatoria (la API la admite vacia)
	errors := []string{}
	if createTaskDto.GetTaskTitle() == "" {
		errors = append(errors, enums.TasksMessageEnum.TASK_TITLE_IS_REQUIRED)
	}
	if createTaskDto.GetTargetDate() == "" {
		errors = append(errors, enums.TasksMessageEnum.TARGET_DATE_IS_REQUIRED)
	}
	if len(errors) > 0 {
		return nil, exceptions.BadRequestCustom(errors)
	}

	apiResponse, err := s.tasksWriterGraphqlRepository.CreateTask(
		map[string]any{
			"taskTitle":       createTaskDto.GetTaskTitle(),
			"taskStatus":      createTaskDto.GetTaskStatus(),
			"taskType":        createTaskDto.GetTaskType(),
			"targetDate":      createTaskDto.GetTargetDate(),
			"taskDescription": createTaskDto.GetTaskDescription(),
		},
		createTaskDto.GetApiToken(),
	)
	if err != nil {
		return nil, err
	}
	if !apiResponse.Status {
		return nil, exceptions.FromApiResponse(apiResponse)
	}

	return CreateTaskResultDtoFromPrimitives(map[string]any{
		"task": apiResponse.Data,
	}), nil
}
