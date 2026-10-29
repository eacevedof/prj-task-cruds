package controllers

import (
	"errors"

	graphqlcontrollers "task-go/src/modules/graphql/infrastructure/controllers"
	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/tasks/application/gettask"
	"task-go/src/modules/tasks/domain/entities"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	usersexceptions "task-go/src/modules/users/domain/exceptions"
)

// GetTaskGraphqlController query task(task_uuid) (src/modules/tasks/infrastructure/providers/tasks.graphql). El 401 de la autenticacion llega como
// UsersException.
type GetTaskGraphqlController struct {
	graphqlcontrollers.AbstractGraphqlController
}

func GetTaskGraphqlControllerGetInstance(httpRequester *httprequester.HttpRequester) *GetTaskGraphqlController {
	return &GetTaskGraphqlController{AbstractGraphqlController: graphqlcontrollers.NewAbstractGraphqlController(httpRequester)}
}

func (c *GetTaskGraphqlController) Invoke(args map[string]any) (*entities.TaskPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "GetTaskGraphqlController.Invoke")
	}
	getTaskResultDto, err := gettask.GetTaskServiceGetInstance().Invoke(
		gettask.GetTaskDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"taskUuid": args[enums.TasksKeyEnum.TASK_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "GetTaskGraphqlController.Invoke")
	}
	task := getTaskResultDto.ToArray()
	return &task, nil
}

// getGraphqlError los catch de task-php: UsersException y TasksException son de dominio; lo demas, inesperado.
func (c *GetTaskGraphqlController) getGraphqlError(err error, logTitle string) error {
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
