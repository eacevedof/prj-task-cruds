package controllers

import (
	"errors"

	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	"task-webassembly/src/modules/users/application/restoresession"
	"task-webassembly/src/modules/users/domain/enums"
	"task-webassembly/src/modules/users/domain/exceptions"
	"task-webassembly/src/modules/users/infrastructure/views"
)

// RestoreSessionController al recargar, la sesion guardada se revalida con su api_token: si sigue valiendo se entra
// (SESSION_STARTED); si no (otro login lo roto, se regenero o se borro la cuenta), sesion caducada.
type RestoreSessionController struct {
	dispatcher *dispatcher.Dispatcher
	logger     *logger.Logger
	subscriber *subscriber.Subscriber
	authView   *views.AuthView
}

func RestoreSessionControllerGetInstance() *RestoreSessionController {
	return &RestoreSessionController{
		dispatcher: dispatcher.GetInstance(),
		logger:     logger.GetInstance(),
		subscriber: subscriber.GetInstance(),
		authView:   views.AuthViewGetInstance(),
	}
}

func (c *RestoreSessionController) Init() {
	c.subscriber.Subscribe(enums.UsersEventEnum.SESSION_RESTORE_REQUESTED, c.Invoke)
}

// Invoke request: {apiToken}
func (c *RestoreSessionController) Invoke(request map[string]any) {
	restoreSessionResultDto, err := restoresession.RestoreSessionServiceGetInstance().Invoke(
		restoresession.RestoreSessionDtoFromPrimitives(map[string]any{
			"apiToken": request["apiToken"],
		}),
	)
	if err != nil {
		c.renderError(err)
		return
	}

	c.dispatcher.Dispatch(enums.UsersEventEnum.SESSION_STARTED, map[string]any{
		"apiToken":  restoreSessionResultDto.GetApiToken(),
		"userUuid":  restoreSessionResultDto.GetUserUuid(),
		"userName":  restoreSessionResultDto.GetUserName(),
		"userEmail": restoreSessionResultDto.GetUserEmail(),
	})
}

func (c *RestoreSessionController) renderError(err error) {
	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) && usersException.GetCode() == sharedenums.HttpCodeEnum.UNAUTHORIZED {
		c.dispatcher.Dispatch(enums.UsersEventEnum.SESSION_EXPIRED, nil)
		return
	}
	if usersException != nil {
		c.authView.RenderErrors(usersException.GetErrors())
		return
	}
	c.logger.LogError("RestoreSessionController.invoke", err)
	c.authView.RenderErrors([]string{enums.UsersMessageEnum.UNEXPECTED_ERROR})
}
