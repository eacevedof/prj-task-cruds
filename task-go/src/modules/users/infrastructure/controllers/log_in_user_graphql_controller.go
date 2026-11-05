package controllers

import (
	"errors"

	"task-go/src/core/config"
	graphqlexceptions "task-go/src/modules/graphql/domain/exceptions"
	"task-go/src/modules/shared/application"
	sharedenums "task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	"task-go/src/modules/users/application/loginuser"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
)

// LogInUserGraphQlController mutation logIn (src/modules/users/infrastructure/providers/users.graphql). Publico (sin token): user_name + password
// -> el usuario con un api_token nuevo. El mismo caso de uso que POST /api/v1/log-in (LogInUserRestController).
type LogInUserGraphQlController struct {
	sharedcontrollers.AbstractHttpController
}

func LogInUserGraphQlControllerGetInstance(httpRequester *httprequester.HttpRequester) *LogInUserGraphQlController {
	return &LogInUserGraphQlController{AbstractHttpController: sharedcontrollers.NewAbstractHttpController(httpRequester)}
}

func (c *LogInUserGraphQlController) Invoke(gqlPayload map[string]any) (*entities.UserWithApiTokenPrimitives, error) {
	logInUserResultDto, err := loginuser.LogInUserServiceGetInstance().Invoke(
		loginuser.LogInUserDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			map[string]any{
				"userName": gqlPayload[enums.UsersKeyEnum.USER_NAME],
				"password": gqlPayload[enums.UsersKeyEnum.PASSWORD],
			},
		)),
	)
	if err != nil {
		return nil, c.getGraphQlError(err, "LogInUserGraphQlController.Invoke")
	}
	user := logInUserResultDto.ToArray()
	return &user, nil
}

// getGraphQlError los dos catch de task-php: UsersException -> ClientAwareExceptionForWebonyx (su
// codigo y su mensaje al cliente); lo demas -> log + mail + InternalServerError (500 con el mensaje generico).
func (c *LogInUserGraphQlController) getGraphQlError(err error, logTitle string) error {
	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		return graphqlexceptions.ClientAwareExceptionForWebonyx(usersException)
	}
	c.LogException(err, logTitle)
	c.SendMailOnThrowable(err, logTitle)
	return graphqlexceptions.InternalServerError(config.Tr(sharedenums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
}
