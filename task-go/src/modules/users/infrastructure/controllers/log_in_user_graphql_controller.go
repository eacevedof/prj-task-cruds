package controllers

import (
	"errors"

	graphqlcontrollers "task-go/src/modules/graphql/infrastructure/controllers"
	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/users/application/loginuser"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
)

// LogInUserGraphqlController mutation logIn (src/modules/users/infrastructure/providers/users.graphql). Publico (sin token): user_name + password
// -> el usuario con un api_token nuevo. El mismo caso de uso que POST /api/v1/log-in (LogInUserRestController).
type LogInUserGraphqlController struct {
	graphqlcontrollers.AbstractGraphqlController
}

func LogInUserGraphqlControllerGetInstance(httpRequester *httprequester.HttpRequester) *LogInUserGraphqlController {
	return &LogInUserGraphqlController{AbstractGraphqlController: graphqlcontrollers.NewAbstractGraphqlController(httpRequester)}
}

func (c *LogInUserGraphqlController) Invoke(args map[string]any) (*entities.UserWithApiTokenPrimitives, error) {
	logInUserResultDto, err := loginuser.LogInUserServiceGetInstance().Invoke(
		loginuser.LogInUserDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			map[string]any{
				"userName": args[enums.UsersKeyEnum.USER_NAME],
				"password": args[enums.UsersKeyEnum.PASSWORD],
			},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "LogInUserGraphqlController.Invoke")
	}
	user := logInUserResultDto.ToArray()
	return &user, nil
}

// getGraphqlError los catch de task-php: UsersException es de dominio; lo demas, inesperado.
func (c *LogInUserGraphqlController) getGraphqlError(err error, logTitle string) error {
	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		return c.GetGraphqlDomainError(usersException)
	}
	return c.GetGraphqlUnexpectedError(err, logTitle)
}
