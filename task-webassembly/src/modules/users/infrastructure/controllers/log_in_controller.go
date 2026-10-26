package controllers

import (
	"errors"

	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	"task-webassembly/src/modules/users/application/login"
	"task-webassembly/src/modules/users/domain/enums"
	"task-webassembly/src/modules/users/domain/exceptions"
	"task-webassembly/src/modules/users/infrastructure/views"
)

// LogInController entrar con user_name + password: con el api_token nuevo que devuelve la API se entra (SESSION_STARTED).
type LogInController struct {
	dispatcher *dispatcher.Dispatcher
	logger     *logger.Logger
	subscriber *subscriber.Subscriber
	authView   *views.AuthView
}

func LogInControllerGetInstance() *LogInController {
	return &LogInController{
		dispatcher: dispatcher.GetInstance(),
		logger:     logger.GetInstance(),
		subscriber: subscriber.GetInstance(),
		authView:   views.AuthViewGetInstance(),
	}
}

func (c *LogInController) Init() {
	c.subscriber.Subscribe(enums.UsersEventEnum.LOG_IN_REQUESTED, c.Invoke)
}

// Invoke request: {userName, password}
func (c *LogInController) Invoke(request map[string]any) {
	logInResultDto, err := login.LogInServiceGetInstance().Invoke(
		login.LogInDtoFromPrimitives(map[string]any{
			"userName": request["userName"],
			"password": request["password"],
		}),
	)
	if err != nil {
		c.renderError(err)
		return
	}

	c.dispatcher.Dispatch(enums.UsersEventEnum.SESSION_STARTED, map[string]any{
		"apiToken":  logInResultDto.GetApiToken(),
		"userUuid":  logInResultDto.GetUserUuid(),
		"userName":  logInResultDto.GetUserName(),
		"userEmail": logInResultDto.GetUserEmail(),
	})
}

func (c *LogInController) renderError(err error) {
	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		c.authView.RenderErrors(usersException.GetErrors())
		return
	}
	c.logger.LogError("LogInController.invoke", err)
	c.authView.RenderErrors([]string{enums.UsersMessageEnum.UNEXPECTED_ERROR})
}
