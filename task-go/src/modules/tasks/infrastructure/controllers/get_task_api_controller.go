package controllers

import (
	"errors"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	"task-go/src/modules/tasks/application/gettask"
	"task-go/src/modules/tasks/domain/entities"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	usersexceptions "task-go/src/modules/users/domain/exceptions"
)

// GetTaskApiController query task(task_uuid) (src/core/graphql/schema.graphql). El 401 de la autenticacion llega como
// UsersException.
type GetTaskApiController struct {
	sharedcontrollers.AbstractGraphqlController
}

func GetTaskApiControllerGetInstance(httpRequester *httprequester.HttpRequester) *GetTaskApiController {
	return &GetTaskApiController{AbstractGraphqlController: sharedcontrollers.NewAbstractGraphqlController(httpRequester)}
}

func (c *GetTaskApiController) Invoke(args map[string]any) (*entities.TaskPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "GetTaskApiController.Invoke")
	}
	getTaskResultDto, err := gettask.GetTaskServiceGetInstance().Invoke(
		gettask.GetTaskDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"taskUuid": args[enums.TasksKeyEnum.TASK_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "GetTaskApiController.Invoke")
	}
	task := getTaskResultDto.ToArray()
	return &task, nil
}

// getGraphqlError los catch de task-php: UsersException y TasksException son de dominio; lo demas, inesperado.
func (c *GetTaskApiController) getGraphqlError(err error, logTitle string) error {
	var usersException *usersexceptions.UsersException
	var tasksException *exceptions.TasksException
	switch {
	case errors.As(err, &usersException):
		return c.GetGraphqlDomainError(usersException)
	case errors.As(err, &tasksException):
		return c.GetGraphqlDomainError(tasksException)
	default:
		return c.GetGraphqlUnexpectedError(err, logTitle)
	}
}
