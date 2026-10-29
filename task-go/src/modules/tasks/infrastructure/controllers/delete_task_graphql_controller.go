package controllers

import (
	"errors"

	graphqlcontrollers "task-go/src/modules/graphql/infrastructure/controllers"
	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/tasks/application/deletetask"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	usersexceptions "task-go/src/modules/users/domain/exceptions"
)

// DeleteTaskGraphqlController mutation deleteTask(task_uuid) (src/modules/tasks/infrastructure/providers/tasks.graphql). El 401 de la
// autenticacion llega como UsersException.
type DeleteTaskGraphqlController struct {
	graphqlcontrollers.AbstractGraphqlController
}

func DeleteTaskGraphqlControllerGetInstance(httpRequester *httprequester.HttpRequester) *DeleteTaskGraphqlController {
	return &DeleteTaskGraphqlController{AbstractGraphqlController: graphqlcontrollers.NewAbstractGraphqlController(httpRequester)}
}

func (c *DeleteTaskGraphqlController) Invoke(args map[string]any) (*deletetask.DeleteTaskResultPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "DeleteTaskGraphqlController.Invoke")
	}
	deleteTaskResultDto, err := deletetask.DeleteTaskServiceGetInstance().Invoke(
		deletetask.DeleteTaskDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"taskUuid": args[enums.TasksKeyEnum.TASK_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "DeleteTaskGraphqlController.Invoke")
	}
	deleteTaskResult := deleteTaskResultDto.ToArray()
	return &deleteTaskResult, nil
}

// getGraphqlError los catch de task-php: UsersException y TasksException son de dominio; lo demas, inesperado.
func (c *DeleteTaskGraphqlController) getGraphqlError(err error, logTitle string) error {
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
