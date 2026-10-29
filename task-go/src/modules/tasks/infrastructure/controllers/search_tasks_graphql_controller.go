package controllers

import (
	"errors"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/tasks/application/searchtasks"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	usersexceptions "task-go/src/modules/users/domain/exceptions"
)

// SearchTasksGraphqlController query tasks (userUuid "") y userTasks / User.tasks (src/core/graphql/schema.graphql):
// busqueda paginada con filtros. El 401 de la autenticacion llega como UsersException.
type SearchTasksGraphqlController struct {
	sharedcontrollers.AbstractGraphqlController
}

func SearchTasksGraphqlControllerGetInstance(httpRequester *httprequester.HttpRequester) *SearchTasksGraphqlController {
	return &SearchTasksGraphqlController{AbstractGraphqlController: sharedcontrollers.NewAbstractGraphqlController(httpRequester)}
}

// Invoke args {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
func (c *SearchTasksGraphqlController) Invoke(userUuid string, args map[string]any) (*searchtasks.SearchTasksResultPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "SearchTasksGraphqlController.Invoke")
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
		return nil, c.getGraphqlError(err, "SearchTasksGraphqlController.Invoke")
	}
	taskPage := searchTasksResultDto.ToArray()
	return &taskPage, nil
}

// getGraphqlError los catch de task-php: FieldsException, UsersException y TasksException son de dominio; lo demas,
// inesperado.
func (c *SearchTasksGraphqlController) getGraphqlError(err error, logTitle string) error {
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
