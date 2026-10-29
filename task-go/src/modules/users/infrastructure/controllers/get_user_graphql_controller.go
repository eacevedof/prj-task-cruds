package controllers

import (
	"errors"

	graphqlcontrollers "task-go/src/modules/graphql/infrastructure/controllers"
	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/users/application/getuser"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
)

// GetUserGraphqlController query user(user_uuid) (src/modules/users/infrastructure/providers/users.graphql). Exige Bearer.
type GetUserGraphqlController struct {
	graphqlcontrollers.AbstractGraphqlController
}

func GetUserGraphqlControllerGetInstance(httpRequester *httprequester.HttpRequester) *GetUserGraphqlController {
	return &GetUserGraphqlController{AbstractGraphqlController: graphqlcontrollers.NewAbstractGraphqlController(httpRequester)}
}

func (c *GetUserGraphqlController) Invoke(args map[string]any) (*entities.UserPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "GetUserGraphqlController.Invoke")
	}
	getUserResultDto, err := getuser.GetUserServiceGetInstance().Invoke(
		getuser.GetUserDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"userUuid": args[enums.UsersKeyEnum.USER_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "GetUserGraphqlController.Invoke")
	}
	user := getUserResultDto.ToArray()
	return &user, nil
}

// getGraphqlError los catch de task-php: UsersException es de dominio; lo demas, inesperado.
func (c *GetUserGraphqlController) getGraphqlError(err error, logTitle string) error {
	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		return c.GetGraphqlDomainError(usersException)
	}
	return c.GetGraphqlUnexpectedError(err, logTitle)
}
