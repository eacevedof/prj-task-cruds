package controllers

import (
	"errors"

	graphqlcontrollers "task-go/src/modules/graphql/infrastructure/controllers"
	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/tasks/application/updatetask"
	"task-go/src/modules/tasks/domain/entities"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	usersexceptions "task-go/src/modules/users/domain/exceptions"
)

// UpdateTaskGraphqlController mutation updateTask (src/modules/tasks/infrastructure/providers/tasks.graphql). El 401 de la autenticacion llega
// como UsersException.
type UpdateTaskGraphqlController struct {
	graphqlcontrollers.AbstractGraphqlController
}

func UpdateTaskGraphqlControllerGetInstance(httpRequester *httprequester.HttpRequester) *UpdateTaskGraphqlController {
	return &UpdateTaskGraphqlController{AbstractGraphqlController: graphqlcontrollers.NewAbstractGraphqlController(httpRequester)}
}

func (c *UpdateTaskGraphqlController) Invoke(args map[string]any) (*entities.TaskPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "UpdateTaskGraphqlController.Invoke")
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
		return nil, c.getGraphqlError(err, "UpdateTaskGraphqlController.Invoke")
	}
	task := updateTaskResultDto.ToArray()
	return &task, nil
}

// getGraphqlError los catch de task-php: FieldsException, UsersException y TasksException son de dominio; lo demas,
// inesperado.
func (c *UpdateTaskGraphqlController) getGraphqlError(err error, logTitle string) error {
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
