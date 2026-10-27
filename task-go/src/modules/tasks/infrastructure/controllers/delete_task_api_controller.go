package controllers

import (
	"errors"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	"task-go/src/modules/tasks/application/deletetask"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	usersexceptions "task-go/src/modules/users/domain/exceptions"
)

// DeleteTaskApiController mutation deleteTask(task_uuid) (src/core/graphql/schema.graphql). El 401 de la
// autenticacion llega como UsersException.
type DeleteTaskApiController struct {
	sharedcontrollers.AbstractGraphqlController
}

func DeleteTaskApiControllerGetInstance(httpRequester *httprequester.HttpRequester) *DeleteTaskApiController {
	return &DeleteTaskApiController{AbstractGraphqlController: sharedcontrollers.NewAbstractGraphqlController(httpRequester)}
}

func (c *DeleteTaskApiController) Invoke(args map[string]any) (*deletetask.DeleteTaskResultPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "DeleteTaskApiController.Invoke")
	}
	deleteTaskResultDto, err := deletetask.DeleteTaskServiceGetInstance().Invoke(
		deletetask.DeleteTaskDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"taskUuid": args[enums.TasksKeyEnum.TASK_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "DeleteTaskApiController.Invoke")
	}
	deleteTaskResult := deleteTaskResultDto.ToArray()
	return &deleteTaskResult, nil
}

// getGraphqlError los catch de task-php: UsersException y TasksException son de dominio; lo demas, inesperado.
func (c *DeleteTaskApiController) getGraphqlError(err error, logTitle string) error {
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
