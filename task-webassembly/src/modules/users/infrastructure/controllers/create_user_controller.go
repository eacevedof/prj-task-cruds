package controllers

import (
	"errors"

	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	"task-webassembly/src/modules/users/application/createuser"
	"task-webassembly/src/modules/users/domain/enums"
	"task-webassembly/src/modules/users/domain/exceptions"
	"task-webassembly/src/modules/users/infrastructure/views"
)

// CreateUserController crear cuenta (sin token): con el api_token que devuelve el alta se entra directamente (SESSION_STARTED).
type CreateUserController struct {
	dispatcher *dispatcher.Dispatcher
	logger     *logger.Logger
	subscriber *subscriber.Subscriber
	authView   *views.AuthView
}

func CreateUserControllerGetInstance() *CreateUserController {
	return &CreateUserController{
		dispatcher: dispatcher.GetInstance(),
		logger:     logger.GetInstance(),
		subscriber: subscriber.GetInstance(),
		authView:   views.AuthViewGetInstance(),
	}
}

func (c *CreateUserController) Init() {
	c.subscriber.Subscribe(enums.UsersEventEnum.SIGN_UP_REQUESTED, c.Invoke)
}

// Invoke request: {userName, userEmail, password}
func (c *CreateUserController) Invoke(request map[string]any) {
	createUserResultDto, err := createuser.CreateUserServiceGetInstance().Invoke(
		createuser.CreateUserDtoFromPrimitives(map[string]any{
			"userName":  request["userName"],
			"userEmail": request["userEmail"],
			"password":  request["password"],
		}),
	)
	if err != nil {
		c.renderError(err)
		return
	}

	c.dispatcher.Dispatch(enums.UsersEventEnum.SESSION_STARTED, map[string]any{
		"apiToken":  createUserResultDto.GetApiToken(),
		"userUuid":  createUserResultDto.GetUserUuid(),
		"userName":  createUserResultDto.GetUserName(),
		"userEmail": createUserResultDto.GetUserEmail(),
	})
}

func (c *CreateUserController) renderError(err error) {
	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		c.authView.RenderErrors(usersException.GetErrors())
		return
	}
	c.logger.LogError("CreateUserController.invoke", err)
	c.authView.RenderErrors([]string{enums.UsersMessageEnum.UNEXPECTED_ERROR})
}
