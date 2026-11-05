package controllers

import (
	"errors"

	"task-go/src/core/config"
	graphqlexceptions "task-go/src/modules/graphql/domain/exceptions"
	"task-go/src/modules/shared/application"
	sharedenums "task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	"task-go/src/modules/tasks/application/gettask"
	"task-go/src/modules/tasks/domain/entities"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	usersexceptions "task-go/src/modules/users/domain/exceptions"
)

// GetTaskGraphQlController query task(task_uuid) (src/modules/tasks/infrastructure/providers/tasks.graphql). El 401 de la autenticacion llega como
// UsersException.
type GetTaskGraphQlController struct {
	sharedcontrollers.AbstractHttpController
}

func GetTaskGraphQlControllerGetInstance(httpRequester *httprequester.HttpRequester) *GetTaskGraphQlController {
	return &GetTaskGraphQlController{AbstractHttpController: sharedcontrollers.NewAbstractHttpController(httpRequester)}
}

func (c *GetTaskGraphQlController) Invoke(gqlPayload map[string]any) (*entities.TaskPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphQlError(err, "GetTaskGraphQlController.Invoke")
	}
	getTaskResultDto, err := gettask.GetTaskServiceGetInstance().Invoke(
		gettask.GetTaskDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"taskUuid": gqlPayload[enums.TasksKeyEnum.TASK_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphQlError(err, "GetTaskGraphQlController.Invoke")
	}
	task := getTaskResultDto.ToArray()
	return &task, nil
}

// getGraphQlError los dos catch de task-php: UsersException y TasksException -> ClientAwareExceptionForWebonyx (su
// codigo y su mensaje al cliente); lo demas -> log + mail + InternalServerError (500 con el mensaje generico).
func (c *GetTaskGraphQlController) getGraphQlError(err error, logTitle string) error {
	var usersException *usersexceptions.UsersException
	var tasksException *exceptions.TasksException
	switch {
	case errors.As(err, &usersException):
		return graphqlexceptions.ClientAwareExceptionForWebonyx(usersException)
	case errors.As(err, &tasksException):
		return graphqlexceptions.ClientAwareExceptionForWebonyx(tasksException)
	default:
		c.LogException(err, logTitle)
		c.SendMailOnThrowable(err, logTitle)
		return graphqlexceptions.InternalServerError(config.Tr(sharedenums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
	}
}
