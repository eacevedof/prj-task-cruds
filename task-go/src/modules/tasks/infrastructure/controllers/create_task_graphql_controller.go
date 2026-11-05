package controllers

import (
	"errors"

	"task-go/src/core/config"
	graphqlexceptions "task-go/src/modules/graphql/domain/exceptions"
	"task-go/src/modules/shared/application"
	sharedenums "task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/tasks/application/createtask"
	"task-go/src/modules/tasks/domain/entities"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	usersexceptions "task-go/src/modules/users/domain/exceptions"
)

// CreateTaskGraphQlController mutation createTask (src/modules/tasks/infrastructure/providers/tasks.graphql): el dueno es el usuario del token.
// El 401 de la autenticacion llega como UsersException.
type CreateTaskGraphQlController struct {
	sharedcontrollers.AbstractHttpController
}

func CreateTaskGraphQlControllerGetInstance(httpRequester *httprequester.HttpRequester) *CreateTaskGraphQlController {
	return &CreateTaskGraphQlController{AbstractHttpController: sharedcontrollers.NewAbstractHttpController(httpRequester)}
}

func (c *CreateTaskGraphQlController) Invoke(gqlPayload map[string]any) (*entities.TaskPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphQlError(err, "CreateTaskGraphQlController.Invoke")
	}
	createTaskResultDto, err := createtask.CreateTaskServiceGetInstance().Invoke(
		createtask.CreateTaskDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{
				"taskTitle":       gqlPayload[enums.TasksKeyEnum.TASK_TITLE],
				"taskStatus":      gqlPayload[enums.TasksKeyEnum.TASK_STATUS],
				"taskType":        gqlPayload[enums.TasksKeyEnum.TASK_TYPE],
				"targetDate":      gqlPayload[enums.TasksKeyEnum.TARGET_DATE],
				"taskDescription": gqlPayload[enums.TasksKeyEnum.TASK_DESCRIPTION],
			},
		)),
	)
	if err != nil {
		return nil, c.getGraphQlError(err, "CreateTaskGraphQlController.Invoke")
	}
	task := createTaskResultDto.ToArray()
	return &task, nil
}

// getGraphQlError los dos catch de task-php: FieldsException, UsersException y TasksException -> ClientAwareExceptionForWebonyx (su
// codigo y su mensaje al cliente); lo demas -> log + mail + InternalServerError (500 con el mensaje generico).
func (c *CreateTaskGraphQlController) getGraphQlError(err error, logTitle string) error {
	var fieldsException *sharedexceptions.FieldsException
	var usersException *usersexceptions.UsersException
	var tasksException *exceptions.TasksException
	switch {
	case errors.As(err, &fieldsException):
		return graphqlexceptions.ClientAwareExceptionForWebonyx(fieldsException)
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
