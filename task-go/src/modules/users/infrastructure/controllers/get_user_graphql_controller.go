package controllers

import (
	"errors"

	"task-go/src/core/config"
	graphqlexceptions "task-go/src/modules/graphql/domain/exceptions"
	"task-go/src/modules/shared/application"
	sharedenums "task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	"task-go/src/modules/users/application/getuser"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
)

// GetUserGraphQlController query user(user_uuid) (src/modules/users/infrastructure/providers/users.graphql). Exige Bearer.
type GetUserGraphQlController struct {
	sharedcontrollers.AbstractHttpController
}

func GetUserGraphQlControllerGetInstance(httpRequester *httprequester.HttpRequester) *GetUserGraphQlController {
	return &GetUserGraphQlController{AbstractHttpController: sharedcontrollers.NewAbstractHttpController(httpRequester)}
}

func (c *GetUserGraphQlController) Invoke(gqlPayload map[string]any) (*entities.UserPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphQlError(err, "GetUserGraphQlController.Invoke")
	}
	getUserResultDto, err := getuser.GetUserServiceGetInstance().Invoke(
		getuser.GetUserDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"userUuid": gqlPayload[enums.UsersKeyEnum.USER_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphQlError(err, "GetUserGraphQlController.Invoke")
	}
	user := getUserResultDto.ToArray()
	return &user, nil
}

// getGraphQlError los dos catch de task-php: UsersException -> ClientAwareExceptionForWebonyx (su
// codigo y su mensaje al cliente); lo demas -> log + mail + InternalServerError (500 con el mensaje generico).
func (c *GetUserGraphQlController) getGraphQlError(err error, logTitle string) error {
	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		return graphqlexceptions.ClientAwareExceptionForWebonyx(usersException)
	}
	c.LogException(err, logTitle)
	c.SendMailOnThrowable(err, logTitle)
	return graphqlexceptions.InternalServerError(config.Tr(sharedenums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
}
