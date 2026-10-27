package controllers

import (
	"task-webassembly/src/modules/shared/application"
	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/graphqlrequester"
	"task-webassembly/src/modules/shared/infrastructure/components/localstorer"
	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/views"
)

// ApplySavedBackendController arranque, con la configuracion ya leida (/config.json): la API de las llamadas es la
// del back guardado en el navegador (localStorage). Un back guardado que ya no esta en la config cae en el de por
// defecto (o en el primero). Cambiarlo es cosa de ChangeBackendController.
type ApplySavedBackendController struct {
	localStorer      *localstorer.LocalStorer
	logger           *logger.Logger
	graphqlRequester *graphqlrequester.GraphqlRequester
	navigationView   *views.NavigationView
	requestTimesView *views.RequestTimesView
}

func ApplySavedBackendControllerGetInstance() *ApplySavedBackendController {
	return &ApplySavedBackendController{
		localStorer:      localstorer.GetInstance(),
		logger:           logger.GetInstance(),
		graphqlRequester: graphqlrequester.GetInstance(),
		navigationView:   views.NavigationViewGetInstance(),
		requestTimesView: views.RequestTimesViewGetInstance(),
	}
}

// Invoke backends: [{code, name, apiBaseUrl}]
func (c *ApplySavedBackendController) Invoke(backends []map[string]any) {
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

func (c *ApplySavedBackendController) findBackend(backends []map[string]any, backendCode string) map[string]any {
	for _, backend := range backends {
		if application.ToString(backend["code"]) == backendCode {
			return backend
		}
	}
	return nil
}

func (c *ApplySavedBackendController) getSavedBackendCode() string {
	savedBackendCode, err := c.localStorer.GetItem(sharedenums.StorageKeyEnum.BACKEND_CODE)
	if err != nil {
		c.logger.LogError("ApplySavedBackendController.getSavedBackendCode", err)
		return sharedenums.BackendCodeEnum.DEFAULT
	}
	if savedBackendCode == nil {
		return sharedenums.BackendCodeEnum.DEFAULT
	}
	return application.ToString(savedBackendCode)
}
