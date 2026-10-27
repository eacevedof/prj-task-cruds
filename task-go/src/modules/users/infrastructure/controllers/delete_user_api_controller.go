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

// DeleteUserApiController mutation deleteUser(user_uuid) (src/core/graphql/schema.graphql). Exige Bearer.
type DeleteUserApiController struct {
	sharedcontrollers.AbstractGraphqlController
}

func DeleteUserApiControllerGetInstance(httpRequester *httprequester.HttpRequester) *DeleteUserApiController {
	return &DeleteUserApiController{AbstractGraphqlController: sharedcontrollers.NewAbstractGraphqlController(httpRequester)}
}

func (c *DeleteUserApiController) Invoke(args map[string]any) (*deleteuser.DeleteUserResultPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "DeleteUserApiController.Invoke")
	}
	deleteUserResultDto, err := deleteuser.DeleteUserServiceGetInstance().Invoke(
		deleteuser.DeleteUserDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"userUuid": args[enums.UsersKeyEnum.USER_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "DeleteUserApiController.Invoke")
	}
	deleteUserResult := deleteUserResultDto.ToArray()
	return &deleteUserResult, nil
}

// getGraphqlError los catch de task-php: UsersException es de dominio; lo demas, inesperado.
func (c *DeleteUserApiController) getGraphqlError(err error, logTitle string) error {
	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		return c.GetGraphqlDomainError(usersException)
	}
	return c.GetGraphqlUnexpectedError(err, logTitle)
}
