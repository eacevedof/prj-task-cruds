package controllers

import (
	"task-webassembly/src/modules/shared/application"
	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/graphqlrequester"
	"task-webassembly/src/modules/shared/infrastructure/components/localstorer"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/sessionstorer"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	"task-webassembly/src/modules/shared/infrastructure/views"
)

// ChangeBackendController back que consume la SPA (php, go...): preferencia del navegador (localStorage), no de la
// API, asi que no hay caso de uso. Los backs los da /config.json; al arrancar se aplica el guardado (o el de por
// defecto). Cambiar de back cierra la sesion (cada back tiene su BD: el api_token de uno no vale en otro) y recarga.
type ChangeBackendController struct {
	localStorer      *localstorer.LocalStorer
	logger           *logger.Logger
	sessionStorer    *sessionstorer.SessionStorer
	subscriber       *subscriber.Subscriber
	graphqlRequester *graphqlrequester.GraphqlRequester
	navigationView   *views.NavigationView
	requestTimesView *views.RequestTimesView
}

func ChangeBackendControllerGetInstance() *ChangeBackendController {
	return &ChangeBackendController{
		localStorer:      localstorer.GetInstance(),
		logger:           logger.GetInstance(),
		sessionStorer:    sessionstorer.GetInstance(),
		subscriber:       subscriber.GetInstance(),
		graphqlRequester: graphqlrequester.GetInstance(),
		navigationView:   views.NavigationViewGetInstance(),
		requestTimesView: views.RequestTimesViewGetInstance(),
	}
}

func (c *ChangeBackendController) Init() {
	c.subscriber.Subscribe(sharedenums.SharedEventEnum.BACKEND_CHANGE_REQUESTED, c.Invoke)
}

// ApplySavedBackend arranque, con la configuracion ya leida: la API de las llamadas es la del back guardado.
// Un back guardado que ya no esta en la config cae en el de por defecto (o en el primero).
// backends: [{code, name, apiBaseUrl}]
func (c *ChangeBackendController) ApplySavedBackend(backends []map[string]any) {
	if len(backends) == 0 {
		return
	}
	backend := c.findBackend(backends, c.getSavedBackendCode())
	if backend == nil {
		backend = c.findBackend(backends, sharedenums.BackendCodeEnum.DEFAULT)
	}
	if backend == nil {
		backend = backends[0]
	}

	c.graphqlRequester.SetApiBaseUrl(application.ToString(backend["apiBaseUrl"]))
	c.navigationView.RenderBackends(backends, application.ToString(backend["code"]))
	c.requestTimesView.RenderBackend(application.ToString(backend["name"]), application.ToString(backend["apiBaseUrl"]))
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

func (c *ChangeBackendController) findBackend(backends []map[string]any, backendCode string) map[string]any {
	for _, backend := range backends {
		if application.ToString(backend["code"]) == backendCode {
			return backend
		}
	}
	return nil
}

func (c *ChangeBackendController) getSavedBackendCode() string {
	savedBackendCode, err := c.localStorer.GetItem(sharedenums.StorageKeyEnum.BACKEND_CODE)
	if err != nil {
		c.logger.LogError("ChangeBackendController.getSavedBackendCode", err)
		return sharedenums.BackendCodeEnum.DEFAULT
	}
	if savedBackendCode == nil {
		return sharedenums.BackendCodeEnum.DEFAULT
	}
	return application.ToString(savedBackendCode)
}
