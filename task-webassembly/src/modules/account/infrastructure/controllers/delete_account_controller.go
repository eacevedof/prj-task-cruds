package controllers

import (
	"errors"

	"task-webassembly/src/modules/account/application/deleteaccount"
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

// DeleteAccountController borrar la propia cuenta: tras borrarla no hay sesion que mantener, se sale con su aviso.
type DeleteAccountController struct {
	dispatcher    *dispatcher.Dispatcher
	logger        *logger.Logger
	sessionStorer *sessionstorer.SessionStorer
	subscriber    *subscriber.Subscriber
	accountView   *views.AccountView
}

func DeleteAccountControllerGetInstance() *DeleteAccountController {
	return &DeleteAccountController{
		dispatcher:    dispatcher.GetInstance(),
		logger:        logger.GetInstance(),
		sessionStorer: sessionstorer.GetInstance(),
		subscriber:    subscriber.GetInstance(),
		accountView:   views.AccountViewGetInstance(),
	}
}

func (c *DeleteAccountController) Init() {
	c.subscriber.Subscribe(enums.AccountEventEnum.DELETE_ACCOUNT_REQUESTED, func(map[string]any) {
		c.Invoke()
	})
}

func (c *DeleteAccountController) Invoke() {
	session, err := c.sessionStorer.GetSession()
	if err != nil {
		c.renderError(err)
		return
	}
	if _, err := deleteaccount.DeleteAccountServiceGetInstance().Invoke(
		deleteaccount.DeleteAccountDtoFromPrimitives(map[string]any{
			"apiToken": session["apiToken"],
			"userUuid": session["userUuid"],
		}),
	); err != nil {
		c.renderError(err)
		return
	}

	c.dispatcher.Dispatch(usersenums.UsersEventEnum.LOG_OUT_REQUESTED, map[string]any{
		"messageKey": enums.AccountTextEnum.ACCOUNT_DELETED,
	})
}

func (c *DeleteAccountController) renderError(err error) {
	var accountException *exceptions.AccountException
	if errors.As(err, &accountException) && accountException.GetCode() == sharedenums.HttpCodeEnum.UNAUTHORIZED {
		c.dispatcher.Dispatch(usersenums.UsersEventEnum.SESSION_EXPIRED, nil)
		return
	}
	if accountException != nil {
		c.accountView.RenderErrors(accountException.GetErrors())
		return
	}
	c.logger.LogError("DeleteAccountController.invoke", err)
	c.accountView.RenderErrors([]string{enums.AccountMessageEnum.UNEXPECTED_ERROR})
}
