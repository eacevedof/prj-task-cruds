package controllers

import (
	"errors"

	"task-go/src/core/config"
	graphqlexceptions "task-go/src/modules/graphql/domain/exceptions"
	"task-go/src/modules/shared/application"
	sharedenums "task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	"task-go/src/modules/users/application/deleteuser"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
)

// DeleteUserGraphQlController mutation deleteUser(user_uuid) (src/modules/users/infrastructure/providers/users.graphql). Exige Bearer.
type DeleteUserGraphQlController struct {
	sharedcontrollers.AbstractHttpController
}

func DeleteUserGraphQlControllerGetInstance(httpRequester *httprequester.HttpRequester) *DeleteUserGraphQlController {
	return &DeleteUserGraphQlController{AbstractHttpController: sharedcontrollers.NewAbstractHttpController(httpRequester)}
}

func (c *DeleteUserGraphQlController) Invoke(gqlPayload map[string]any) (*deleteuser.DeleteUserResultPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphQlError(err, "DeleteUserGraphQlController.Invoke")
	}
	deleteUserResultDto, err := deleteuser.DeleteUserServiceGetInstance().Invoke(
		deleteuser.DeleteUserDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"userUuid": gqlPayload[enums.UsersKeyEnum.USER_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphQlError(err, "DeleteUserGraphQlController.Invoke")
	}
	deleteUserResult := deleteUserResultDto.ToArray()
	return &deleteUserResult, nil
}

// getGraphQlError los dos catch de task-php: UsersException -> ClientAwareExceptionForWebonyx (su
// codigo y su mensaje al cliente); lo demas -> log + mail + InternalServerError (500 con el mensaje generico).
func (c *DeleteUserGraphQlController) getGraphQlError(err error, logTitle string) error {
	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		return graphqlexceptions.ClientAwareExceptionForWebonyx(usersException)
	}
	c.LogException(err, logTitle)
	c.SendMailOnThrowable(err, logTitle)
	return graphqlexceptions.InternalServerError(config.Tr(sharedenums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
}
