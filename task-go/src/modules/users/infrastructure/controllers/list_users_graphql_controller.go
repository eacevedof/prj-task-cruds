package controllers

import (
	"errors"

	"task-go/src/core/config"
	graphqlexceptions "task-go/src/modules/graphql/domain/exceptions"
	"task-go/src/modules/shared/application"
	sharedenums "task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	"task-go/src/modules/users/application/listusers"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/exceptions"
)

// ListUsersGraphQlController query users (src/modules/users/infrastructure/providers/users.graphql). Exige Bearer.
type ListUsersGraphQlController struct {
	sharedcontrollers.AbstractHttpController
}

func ListUsersGraphQlControllerGetInstance(httpRequester *httprequester.HttpRequester) *ListUsersGraphQlController {
	return &ListUsersGraphQlController{AbstractHttpController: sharedcontrollers.NewAbstractHttpController(httpRequester)}
}

func (c *ListUsersGraphQlController) Invoke() ([]entities.UserPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphQlError(err, "ListUsersGraphQlController.Invoke")
	}
	listUsersResultDto, err := listusers.ListUsersServiceGetInstance().Invoke(
		listusers.ListUsersDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
		)),
	)
	if err != nil {
		return nil, c.getGraphQlError(err, "ListUsersGraphQlController.Invoke")
	}
	return listUsersResultDto.ToArray(), nil
}

// getGraphQlError los dos catch de task-php: UsersException -> ClientAwareExceptionForWebonyx (su
// codigo y su mensaje al cliente); lo demas -> log + mail + InternalServerError (500 con el mensaje generico).
func (c *ListUsersGraphQlController) getGraphQlError(err error, logTitle string) error {
	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		return graphqlexceptions.ClientAwareExceptionForWebonyx(usersException)
	}
	c.LogException(err, logTitle)
	c.SendMailOnThrowable(err, logTitle)
	return graphqlexceptions.InternalServerError(config.Tr(sharedenums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
}
