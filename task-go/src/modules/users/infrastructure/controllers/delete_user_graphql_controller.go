package controllers

import (
	"errors"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	"task-go/src/modules/users/application/deleteuser"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
)

// DeleteUserGraphqlController mutation deleteUser(user_uuid) (src/core/graphql/schema.graphql). Exige Bearer.
type DeleteUserGraphqlController struct {
	sharedcontrollers.AbstractGraphqlController
}

func DeleteUserGraphqlControllerGetInstance(httpRequester *httprequester.HttpRequester) *DeleteUserGraphqlController {
	return &DeleteUserGraphqlController{AbstractGraphqlController: sharedcontrollers.NewAbstractGraphqlController(httpRequester)}
}

func (c *DeleteUserGraphqlController) Invoke(args map[string]any) (*deleteuser.DeleteUserResultPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "DeleteUserGraphqlController.Invoke")
	}
	deleteUserResultDto, err := deleteuser.DeleteUserServiceGetInstance().Invoke(
		deleteuser.DeleteUserDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"userUuid": args[enums.UsersKeyEnum.USER_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "DeleteUserGraphqlController.Invoke")
	}
	deleteUserResult := deleteUserResultDto.ToArray()
	return &deleteUserResult, nil
}

// getGraphqlError los catch de task-php: UsersException es de dominio; lo demas, inesperado.
func (c *DeleteUserGraphqlController) getGraphqlError(err error, logTitle string) error {
	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		return c.GetGraphqlDomainError(usersException)
	}
	return c.GetGraphqlUnexpectedError(err, logTitle)
}
