package controllers

import (
	"errors"
	"maps"

	"task-webassembly/src/modules/account/application/regenerateapitoken"
	"task-webassembly/src/modules/account/domain/enums"
	"task-webassembly/src/modules/account/domain/exceptions"
	"task-webassembly/src/modules/account/infrastructure/views"
	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/sessionstorer"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	usersenums "task-webassembly/src/modules/users/domain/enums"
)

// RegenerateApiTokenController rotar el api_token: la sesion pasa a usar el nuevo (el viejo ya no vale) y la vista lo
// ensena una vez.
type RegenerateApiTokenController struct {
	dispatcher    *dispatcher.Dispatcher
	logger        *logger.Logger
	sessionStorer *sessionstorer.SessionStorer
	subscriber    *subscriber.Subscriber
	accountView   *views.AccountView
}

func RegenerateApiTokenControllerGetInstance() *RegenerateApiTokenController {
	return &RegenerateApiTokenController{
		dispatcher:    dispatcher.GetInstance(),
		logger:        logger.GetInstance(),
		sessionStorer: sessionstorer.GetInstance(),
		subscriber:    subscriber.GetInstance(),
		accountView:   views.AccountViewGetInstance(),
	}
}

func (c *RegenerateApiTokenController) Init() {
	c.subscriber.Subscribe(enums.AccountEventEnum.REGENERATE_API_TOKEN_REQUESTED, func(map[string]any) {
		c.Invoke()
	})
}

func (c *RegenerateApiTokenController) Invoke() {
	session, err := c.sessionStorer.GetSession()
	if err != nil {
		c.renderError(err)
		return
	}
	regenerateApiTokenResultDto, err := regenerateapitoken.RegenerateApiTokenServiceGetInstance().Invoke(
		regenerateapitoken.RegenerateApiTokenDtoFromPrimitives(map[string]any{
			"apiToken": session["apiToken"],
			"userUuid": session["userUuid"],
		}),
	)
	if err != nil {
		c.renderError(err)
		return
	}

	newSession := maps.Clone(session)
	if newSession == nil {
		newSession = map[string]any{}
	}
	newSession["apiToken"] = regenerateApiTokenResultDto.GetApiToken()
	if err := c.sessionStorer.SaveSession(newSession); err != nil {
		c.renderError(err)
		return
	}
	c.accountView.RenderNewApiToken(regenerateApiTokenResultDto.GetApiToken())
}

func (c *RegenerateApiTokenController) renderError(err error) {
	var accountException *exceptions.AccountException
	if errors.As(err, &accountException) && accountException.GetCode() == sharedenums.HttpCodeEnum.UNAUTHORIZED {
		c.dispatcher.Dispatch(usersenums.UsersEventEnum.SESSION_EXPIRED, nil)
		return
	}
	if accountException != nil {
		c.accountView.RenderErrors(accountException.GetErrors())
		return
	}
	c.logger.LogError("RegenerateApiTokenController.invoke", err)
	c.accountView.RenderErrors([]string{enums.AccountMessageEnum.UNEXPECTED_ERROR})
}
