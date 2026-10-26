package controllers

import (
	"errors"
	"maps"

	"task-webassembly/src/modules/account/application/updateaccount"
	"task-webassembly/src/modules/account/domain/enums"
	"task-webassembly/src/modules/account/domain/exceptions"
	"task-webassembly/src/modules/account/infrastructure/views"
	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/sessionstorer"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	usersenums "task-webassembly/src/modules/users/domain/enums"
	usersviews "task-webassembly/src/modules/users/infrastructure/views"
)

// UpdateAccountController modificar los datos de la cuenta. Tambien rellena la pantalla al entrar y la limpia al salir:
// Account escucha a Users (y no al reves).
type UpdateAccountController struct {
	dispatcher    *dispatcher.Dispatcher
	logger        *logger.Logger
	sessionStorer *sessionstorer.SessionStorer
	subscriber    *subscriber.Subscriber
	sessionView   *usersviews.SessionView
	accountView   *views.AccountView
}

func UpdateAccountControllerGetInstance() *UpdateAccountController {
	return &UpdateAccountController{
		dispatcher:    dispatcher.GetInstance(),
		logger:        logger.GetInstance(),
		sessionStorer: sessionstorer.GetInstance(),
		subscriber:    subscriber.GetInstance(),
		sessionView:   usersviews.SessionViewGetInstance(),
		accountView:   views.AccountViewGetInstance(),
	}
}

func (c *UpdateAccountController) Init() {
	c.subscriber.Subscribe(enums.AccountEventEnum.UPDATE_ACCOUNT_REQUESTED, c.Invoke)
	c.subscriber.Subscribe(usersenums.UsersEventEnum.USER_LOGGED_IN, func(user map[string]any) {
		c.accountView.Reset()
		c.accountView.RenderUser(user)
	})
	c.subscriber.Subscribe(usersenums.UsersEventEnum.USER_LOGGED_OUT, func(map[string]any) {
		c.accountView.Reset()
	})
}

// Invoke request: {userName, userEmail}
func (c *UpdateAccountController) Invoke(request map[string]any) {
	session, err := c.sessionStorer.GetSession()
	if err != nil {
		c.renderError(err)
		return
	}
	updateAccountResultDto, err := updateaccount.UpdateAccountServiceGetInstance().Invoke(
		updateaccount.UpdateAccountDtoFromPrimitives(map[string]any{
			"apiToken":  session["apiToken"],
			"userUuid":  session["userUuid"],
			"userName":  request["userName"],
			"userEmail": request["userEmail"],
		}),
	)
	if err != nil {
		c.renderError(err)
		return
	}

	user := map[string]any{
		"userUuid":  updateAccountResultDto.GetUserUuid(),
		"userName":  updateAccountResultDto.GetUserName(),
		"userEmail": updateAccountResultDto.GetUserEmail(),
	}
	newSession := maps.Clone(session)
	if newSession == nil {
		newSession = map[string]any{}
	}
	maps.Copy(newSession, user)
	if err := c.sessionStorer.SaveSession(newSession); err != nil {
		c.renderError(err)
		return
	}
	c.sessionView.RenderUser(user)
	c.accountView.RenderUser(user)
	c.accountView.RenderSuccess(enums.AccountTextEnum.UPDATED)
}

func (c *UpdateAccountController) renderError(err error) {
	var accountException *exceptions.AccountException
	if errors.As(err, &accountException) && accountException.GetCode() == sharedenums.HttpCodeEnum.UNAUTHORIZED {
		c.dispatcher.Dispatch(usersenums.UsersEventEnum.SESSION_EXPIRED, nil)
		return
	}
	if accountException != nil {
		c.accountView.RenderErrors(accountException.GetErrors())
		return
	}
	c.logger.LogError("UpdateAccountController.invoke", err)
	c.accountView.RenderErrors([]string{enums.AccountMessageEnum.UNEXPECTED_ERROR})
}
