package controllers

import (
	"errors"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/tasks/application/createtask"
	"task-go/src/modules/tasks/domain/entities"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	usersexceptions "task-go/src/modules/users/domain/exceptions"
)

// CreateTaskApiController mutation createTask (src/core/graphql/schema.graphql): el dueno es el usuario del token.
// El 401 de la autenticacion llega como UsersException.
type CreateTaskApiController struct {
	sharedcontrollers.AbstractGraphqlController
}

func CreateTaskApiControllerGetInstance(httpRequester *httprequester.HttpRequester) *CreateTaskApiController {
	return &CreateTaskApiController{AbstractGraphqlController: sharedcontrollers.NewAbstractGraphqlController(httpRequester)}
}

func (c *CreateTaskApiController) Invoke(args map[string]any) (*entities.TaskPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "CreateTaskApiController.Invoke")
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
		return nil, c.getGraphqlError(err, "CreateTaskApiController.Invoke")
	}
	task := createTaskResultDto.ToArray()
	return &task, nil
}

// getGraphqlError los catch de task-php: FieldsException, UsersException y TasksException son de dominio; lo demas,
// inesperado.
func (c *CreateTaskApiController) getGraphqlError(err error, logTitle string) error {
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
