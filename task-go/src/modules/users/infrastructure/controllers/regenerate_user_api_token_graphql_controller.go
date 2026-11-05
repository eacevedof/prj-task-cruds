package controllers

import (
	"errors"

	"task-go/src/core/config"
	graphqlexceptions "task-go/src/modules/graphql/domain/exceptions"
	"task-go/src/modules/shared/application"
	sharedenums "task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	"task-go/src/modules/users/application/regenerateuserapitoken"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
)

// RegenerateUserApiTokenGraphQlController mutation regenerateApiToken(user_uuid) (src/modules/users/infrastructure/providers/users.graphql).
// Exige Bearer.
type RegenerateUserApiTokenGraphQlController struct {
	sharedcontrollers.AbstractHttpController
}

func RegenerateUserApiTokenGraphQlControllerGetInstance(
	httpRequester *httprequester.HttpRequester,
) *RegenerateUserApiTokenGraphQlController {
	return &RegenerateUserApiTokenGraphQlController{
		AbstractHttpController: sharedcontrollers.NewAbstractHttpController(httpRequester),
	}
}

func (c *RegenerateUserApiTokenGraphQlController) Invoke(
	gqlPayload map[string]any,
) (*regenerateuserapitoken.RegenerateUserApiTokenResultPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphQlError(err, "RegenerateUserApiTokenGraphQlController.Invoke")
	}
	regenerateUserApiTokenResultDto, err := regenerateuserapitoken.RegenerateUserApiTokenServiceGetInstance().Invoke(
		regenerateuserapitoken.RegenerateUserApiTokenDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"userUuid": gqlPayload[enums.UsersKeyEnum.USER_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphQlError(err, "RegenerateUserApiTokenGraphQlController.Invoke")
	}
	apiTokenResult := regenerateUserApiTokenResultDto.ToArray()
	return &apiTokenResult, nil
}

// getGraphQlError los dos catch de task-php: UsersException -> ClientAwareExceptionForWebonyx (su
// codigo y su mensaje al cliente); lo demas -> log + mail + InternalServerError (500 con el mensaje generico).
func (c *RegenerateUserApiTokenGraphQlController) getGraphQlError(err error, logTitle string) error {
	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		return graphqlexceptions.ClientAwareExceptionForWebonyx(usersException)
	}
	c.LogException(err, logTitle)
	c.SendMailOnThrowable(err, logTitle)
	return graphqlexceptions.InternalServerError(config.Tr(sharedenums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
}
