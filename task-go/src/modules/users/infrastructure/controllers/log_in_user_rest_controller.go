package controllers

import (
	"errors"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	"task-go/src/modules/users/application/loginuser"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
)

// LogInUserRestController POST /api/v1/log-in  body: {user_name, password}  (publico: devuelve el usuario y un api_token
// nuevo)
type LogInUserRestController struct {
	sharedcontrollers.AbstractRestController
}

func LogInUserRestControllerGetInstance(httpRequester *httprequester.HttpRequester) *LogInUserRestController {
	return &LogInUserRestController{AbstractRestController: sharedcontrollers.NewAbstractRestController(httpRequester)}
}

func (c *LogInUserRestController) LogIn() *httpresponder.HttpResponder {
	logInUserResultDto, err := loginuser.LogInUserServiceGetInstance().Invoke(
		loginuser.LogInUserDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			map[string]any{
				"userName": c.GetHttpRequest().GetPostString(enums.UsersKeyEnum.USER_NAME),
				"password": c.GetHttpRequest().GetPostString(enums.UsersKeyEnum.PASSWORD),
			},
		)),
	)
	if err == nil {
		return c.RespondOk(logInUserResultDto.ToArray())
	}

	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		return c.RespondDomainError(usersException)
	}
	return c.RespondUnexpectedError(err, "LogInUserRestController.LogIn")
}
