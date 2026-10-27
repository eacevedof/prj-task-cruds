package controllers

import (
	"task-webassembly/src/modules/shared/application"
	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/localstorer"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/sessionstorer"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	"task-webassembly/src/modules/shared/infrastructure/views"
)

// ChangeBackendController back que consume la SPA (php, go...): preferencia del navegador (localStorage), no de la
// API, asi que no hay caso de uso. Al arrancar lo aplica ApplySavedBackendController.
// Cambiar de back cierra la sesion (cada back tiene su BD: el api_token de uno no vale en otro) y recarga.
type ChangeBackendController struct {
	localStorer    *localstorer.LocalStorer
	logger         *logger.Logger
	sessionStorer  *sessionstorer.SessionStorer
	subscriber     *subscriber.Subscriber
	navigationView *views.NavigationView
}

func ChangeBackendControllerGetInstance() *ChangeBackendController {
	return &ChangeBackendController{
		localStorer:    localstorer.GetInstance(),
		logger:         logger.GetInstance(),
		sessionStorer:  sessionstorer.GetInstance(),
		subscriber:     subscriber.GetInstance(),
		navigationView: views.NavigationViewGetInstance(),
	}
}

func (c *ChangeBackendController) Init() {
	c.subscriber.Subscribe(sharedenums.SharedEventEnum.BACKEND_CHANGE_REQUESTED, c.Invoke)
}

// Invoke request: {backendCode}
func (c *ChangeBackendController) Invoke(request map[string]any) {
	if err := c.localStorer.SetItem(sharedenums.StorageKeyEnum.BACKEND_CODE, application.ToString(request["backendCode"])); err != nil {
		// sin localStorage no se puede recordar el back: se queda en el actual
		c.logger.LogError("ChangeBackendController.invoke", err)
	} else if err := c.sessionStorer.RemoveSession(); err != nil {
		c.logger.LogError("ChangeBackendController.invoke", err)
	}
	c.navigationView.ReloadPage()
}
