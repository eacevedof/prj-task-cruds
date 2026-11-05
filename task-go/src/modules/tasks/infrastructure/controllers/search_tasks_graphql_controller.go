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
	"task-go/src/modules/tasks/application/searchtasks"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/domain/exceptions"
	usersexceptions "task-go/src/modules/users/domain/exceptions"
)

// SearchTasksGraphQlController query tasks (src/modules/tasks/infrastructure/providers/tasks.graphql): las tasks del
// usuario autenticado, paginadas y con filtros. User.tasks (las de un usuario como campo de User) va por
// SearchTasksByUserGraphQlController. El 401 de la autenticacion llega como UsersException.
type SearchTasksGraphQlController struct {
	sharedcontrollers.AbstractHttpController
}

func SearchTasksGraphQlControllerGetInstance(httpRequester *httprequester.HttpRequester) *SearchTasksGraphQlController {
	return &SearchTasksGraphQlController{AbstractHttpController: sharedcontrollers.NewAbstractHttpController(httpRequester)}
}

// Invoke gqlPayload {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
func (c *SearchTasksGraphQlController) Invoke(gqlPayload map[string]any) (*searchtasks.SearchTasksResultPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphQlError(err, "SearchTasksGraphQlController.Invoke")
	}
	filter, _ := gqlPayload[enums.TasksKeyEnum.FILTER].(map[string]any)
	searchTasksResultDto, err := searchtasks.SearchTasksServiceGetInstance().Invoke(
		searchtasks.SearchTasksDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{
				"taskText":       filter[enums.TasksKeyEnum.TASK_TEXT],
				"taskStatus":     filter[enums.TasksKeyEnum.TASK_STATUS],
				"taskType":       filter[enums.TasksKeyEnum.TASK_TYPE],
				"targetDateFrom": filter[enums.TasksKeyEnum.TARGET_DATE_FROM],
				"targetDateTo":   filter[enums.TasksKeyEnum.TARGET_DATE_TO],
				"page":           gqlPayload[enums.TasksKeyEnum.PAGE],
				"pageSize":       gqlPayload[enums.TasksKeyEnum.PAGE_SIZE],
			},
		)),
	)
	if err != nil {
		return nil, c.getGraphQlError(err, "SearchTasksGraphQlController.Invoke")
	}
	taskPage := searchTasksResultDto.ToArray()
	return &taskPage, nil
}

// getGraphQlError los dos catch de task-php: FieldsException, UsersException y TasksException -> ClientAwareExceptionForWebonyx (su
// codigo y su mensaje al cliente); lo demas -> log + mail + InternalServerError (500 con el mensaje generico).
func (c *SearchTasksGraphQlController) getGraphQlError(err error, logTitle string) error {
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
