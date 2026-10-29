package controllers

import (
	"errors"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	"task-go/src/modules/users/application/regenerateuserapitoken"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
)

// RegenerateUserApiTokenGraphqlController mutation regenerateApiToken(user_uuid) (src/core/graphql/schema.graphql).
// Exige Bearer.
type RegenerateUserApiTokenGraphqlController struct {
	sharedcontrollers.AbstractGraphqlController
}

func RegenerateUserApiTokenGraphqlControllerGetInstance(
	httpRequester *httprequester.HttpRequester,
) *RegenerateUserApiTokenGraphqlController {
	return &RegenerateUserApiTokenGraphqlController{
		AbstractGraphqlController: sharedcontrollers.NewAbstractGraphqlController(httpRequester),
	}
}

func (c *RegenerateUserApiTokenGraphqlController) Invoke(
	args map[string]any,
) (*regenerateuserapitoken.RegenerateUserApiTokenResultPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "RegenerateUserApiTokenGraphqlController.Invoke")
	}
	regenerateUserApiTokenResultDto, err := regenerateuserapitoken.RegenerateUserApiTokenServiceGetInstance().Invoke(
		regenerateuserapitoken.RegenerateUserApiTokenDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"userUuid": args[enums.UsersKeyEnum.USER_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "RegenerateUserApiTokenGraphqlController.Invoke")
	}
	apiTokenResult := regenerateUserApiTokenResultDto.ToArray()
	return &apiTokenResult, nil
}

// getGraphqlError los catch de task-php: UsersException es de dominio; lo demas, inesperado.
func (c *RegenerateUserApiTokenGraphqlController) getGraphqlError(err error, logTitle string) error {
	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		return c.GetGraphqlDomainError(usersException)
	}
	return c.GetGraphqlUnexpectedError(err, logTitle)
}
