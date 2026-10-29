package controllers

import (
	"errors"

	graphqlcontrollers "task-go/src/modules/graphql/infrastructure/controllers"
	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/users/application/listusers"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/exceptions"
)

// ListUsersGraphqlController query users (src/modules/users/infrastructure/providers/users.graphql). Exige Bearer.
type ListUsersGraphqlController struct {
	graphqlcontrollers.AbstractGraphqlController
}

func ListUsersGraphqlControllerGetInstance(httpRequester *httprequester.HttpRequester) *ListUsersGraphqlController {
	return &ListUsersGraphqlController{AbstractGraphqlController: graphqlcontrollers.NewAbstractGraphqlController(httpRequester)}
}

func (c *ListUsersGraphqlController) Invoke() ([]entities.UserPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "ListUsersGraphqlController.Invoke")
	}
	listUsersResultDto, err := listusers.ListUsersServiceGetInstance().Invoke(
		listusers.ListUsersDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "ListUsersGraphqlController.Invoke")
	}
	return listUsersResultDto.ToArray(), nil
}

// getGraphqlError los catch de task-php: UsersException es de dominio; lo demas, inesperado.
func (c *ListUsersGraphqlController) getGraphqlError(err error, logTitle string) error {
	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		return c.GetGraphqlDomainError(usersException)
	}
	return c.GetGraphqlUnexpectedError(err, logTitle)
}
