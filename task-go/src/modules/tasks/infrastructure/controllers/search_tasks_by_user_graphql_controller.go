package controllers

import (
	"errors"

	graphqlcontrollers "task-go/src/modules/graphql/infrastructure/controllers"
	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/tasks/application/searchtasks"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	usersexceptions "task-go/src/modules/users/domain/exceptions"
)

// SearchTasksByUserGraphqlController query userTasks y campo User.tasks (src/modules/tasks/infrastructure/providers/tasks.graphql):
// las tasks de un usuario, paginadas y con filtros. El uuid llega aparte porque en User.tasks sale del User padre, no
// de los args. Un usuario que no es el autenticado responde 404. El 401 de la autenticacion llega como UsersException.
type SearchTasksByUserGraphqlController struct {
	graphqlcontrollers.AbstractGraphqlController
}

func SearchTasksByUserGraphqlControllerGetInstance(httpRequester *httprequester.HttpRequester) *SearchTasksByUserGraphqlController {
	return &SearchTasksByUserGraphqlController{AbstractGraphqlController: graphqlcontrollers.NewAbstractGraphqlController(httpRequester)}
}

// Invoke args {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
func (c *SearchTasksByUserGraphqlController) Invoke(userUuid string, args map[string]any) (*searchtasks.SearchTasksResultPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "SearchTasksByUserGraphqlController.Invoke")
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
		return nil, c.getGraphqlError(err, "SearchTasksByUserGraphqlController.Invoke")
	}
	taskPage := searchTasksResultDto.ToArray()
	return &taskPage, nil
}

// getGraphqlError los catch de task-php: FieldsException, UsersException y TasksException son de dominio; lo demas,
// inesperado.
func (c *SearchTasksByUserGraphqlController) getGraphqlError(err error, logTitle string) error {
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
