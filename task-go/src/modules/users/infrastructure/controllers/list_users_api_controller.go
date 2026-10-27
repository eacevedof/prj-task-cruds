package controllers

import (
	"errors"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	"task-go/src/modules/users/application/listusers"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/exceptions"
)

// ListUsersApiController query users (src/core/graphql/schema.graphql). Exige Bearer.
type ListUsersApiController struct {
	sharedcontrollers.AbstractGraphqlController
}

func ListUsersApiControllerGetInstance(httpRequester *httprequester.HttpRequester) *ListUsersApiController {
	return &ListUsersApiController{AbstractGraphqlController: sharedcontrollers.NewAbstractGraphqlController(httpRequester)}
}

func (c *ListUsersApiController) Invoke() ([]entities.UserPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "ListUsersApiController.Invoke")
	}
	listUsersResultDto, err := listusers.ListUsersServiceGetInstance().Invoke(
		listusers.ListUsersDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "ListUsersApiController.Invoke")
	}
	return listUsersResultDto.ToArray(), nil
}

// getGraphqlError los catch de task-php: UsersException es de dominio; lo demas, inesperado.
func (c *ListUsersApiController) getGraphqlError(err error, logTitle string) error {
	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		return c.GetGraphqlDomainError(usersException)
	}
	return c.GetGraphqlUnexpectedError(err, logTitle)
}
