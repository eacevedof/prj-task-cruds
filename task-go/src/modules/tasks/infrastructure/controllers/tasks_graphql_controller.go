package controllers

import (
	"errors"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/tasks/application/createtask"
	"task-go/src/modules/tasks/application/deletetask"
	"task-go/src/modules/tasks/application/gettask"
	"task-go/src/modules/tasks/application/searchtasks"
	"task-go/src/modules/tasks/application/updatetask"
	"task-go/src/modules/tasks/domain/entities"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	usersexceptions "task-go/src/modules/users/domain/exceptions"
)

// TasksGraphqlController resolvers GraphQL de tasks (src/core/graphql/schema.graphql). El 401 de la autenticacion
// llega como UsersException.
type TasksGraphqlController struct {
	sharedcontrollers.AbstractGraphqlController
}

func TasksGraphqlControllerGetInstance(httpRequester *httprequester.HttpRequester) *TasksGraphqlController {
	return &TasksGraphqlController{AbstractGraphqlController: sharedcontrollers.NewAbstractGraphqlController(httpRequester)}
}

// SearchTasks query tasks (userUuid "") y userTasks / User.tasks: busqueda paginada con filtros.
// args {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
func (c *TasksGraphqlController) SearchTasks(userUuid string, args map[string]any) (*searchtasks.SearchTasksResultPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "TasksGraphqlController.SearchTasks")
	}
	filter, _ := args[enums.TasksKeyEnum.FILTER].(map[string]any)
	searchTasksResultDto, err := searchtasks.SearchTasksServiceGetInstance().Invoke(
		searchtasks.SearchTasksDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{
				"userUuid":       userUuid,
				"taskText":       filter[enums.TasksKeyEnum.TASK_TEXT],
				"taskStatus":     filter[enums.TasksKeyEnum.TASK_STATUS],
				"taskType":       filter[enums.TasksKeyEnum.TASK_TYPE],
				"targetDateFrom": filter[enums.TasksKeyEnum.TARGET_DATE_FROM],
				"targetDateTo":   filter[enums.TasksKeyEnum.TARGET_DATE_TO],
				"page":           args[enums.TasksKeyEnum.PAGE],
				"pageSize":       args[enums.TasksKeyEnum.PAGE_SIZE],
			},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "TasksGraphqlController.SearchTasks")
	}
	taskPage := searchTasksResultDto.ToArray()
	return &taskPage, nil
}

func (c *TasksGraphqlController) GetTask(args map[string]any) (*entities.TaskPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "TasksGraphqlController.GetTask")
	}
	getTaskResultDto, err := gettask.GetTaskServiceGetInstance().Invoke(
		gettask.GetTaskDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"taskUuid": args[enums.TasksKeyEnum.TASK_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "TasksGraphqlController.GetTask")
	}
	task := getTaskResultDto.ToArray()
	return &task, nil
}

func (c *TasksGraphqlController) CreateTask(args map[string]any) (*entities.TaskPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "TasksGraphqlController.CreateTask")
	}
	createTaskResultDto, err := createtask.CreateTaskServiceGetInstance().Invoke(
		createtask.CreateTaskDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{
				"taskTitle":       args[enums.TasksKeyEnum.TASK_TITLE],
				"taskStatus":      args[enums.TasksKeyEnum.TASK_STATUS],
				"taskType":        args[enums.TasksKeyEnum.TASK_TYPE],
				"targetDate":      args[enums.TasksKeyEnum.TARGET_DATE],
				"taskDescription": args[enums.TasksKeyEnum.TASK_DESCRIPTION],
			},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "TasksGraphqlController.CreateTask")
	}
	task := createTaskResultDto.ToArray()
	return &task, nil
}

func (c *TasksGraphqlController) UpdateTask(args map[string]any) (*entities.TaskPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "TasksGraphqlController.UpdateTask")
	}
	updateTaskResultDto, err := updatetask.UpdateTaskServiceGetInstance().Invoke(
		updatetask.UpdateTaskDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{
				"taskUuid":        args[enums.TasksKeyEnum.TASK_UUID],
				"taskTitle":       args[enums.TasksKeyEnum.TASK_TITLE],
				"taskStatus":      args[enums.TasksKeyEnum.TASK_STATUS],
				"taskType":        args[enums.TasksKeyEnum.TASK_TYPE],
				"targetDate":      args[enums.TasksKeyEnum.TARGET_DATE],
				"taskDescription": args[enums.TasksKeyEnum.TASK_DESCRIPTION],
			},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "TasksGraphqlController.UpdateTask")
	}
	task := updateTaskResultDto.ToArray()
	return &task, nil
}

func (c *TasksGraphqlController) DeleteTask(args map[string]any) (*deletetask.DeleteTaskResultPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "TasksGraphqlController.DeleteTask")
	}
	deleteTaskResultDto, err := deletetask.DeleteTaskServiceGetInstance().Invoke(
		deletetask.DeleteTaskDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"taskUuid": args[enums.TasksKeyEnum.TASK_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "TasksGraphqlController.DeleteTask")
	}
	deleteTaskResult := deleteTaskResultDto.ToArray()
	return &deleteTaskResult, nil
}

// getGraphqlError los catch de task-php: FieldsException, UsersException y TasksException son de dominio; lo demas,
// inesperado.
func (c *TasksGraphqlController) getGraphqlError(err error, logTitle string) error {
	var fieldsException *sharedexceptions.FieldsException
	var usersException *usersexceptions.UsersException
	var tasksException *exceptions.TasksException
	switch {
	case errors.As(err, &fieldsException):
		return c.GetGraphqlDomainError(fieldsException)
	case errors.As(err, &usersException):
		return c.GetGraphqlDomainError(usersException)
	case errors.As(err, &tasksException):
		return c.GetGraphqlDomainError(tasksException)
	default:
		return c.GetGraphqlUnexpectedError(err, logTitle)
	}
}
