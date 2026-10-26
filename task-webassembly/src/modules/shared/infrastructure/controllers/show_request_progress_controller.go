package controllers

import (
	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	"task-webassembly/src/modules/shared/infrastructure/views"
)

// ShowRequestProgressController progreso de las llamadas a la API: el GraphqlRequester avisa al empezar y al terminar
// cada una, asi que ningun caso de uso tiene que acordarse de abrir o cerrar el modal. No hay caso de uso: es solo interfaz.
type ShowRequestProgressController struct {
	subscriber          *subscriber.Subscriber
	requestProgressView *views.RequestProgressView
}

func ShowRequestProgressControllerGetInstance() *ShowRequestProgressController {
	return &ShowRequestProgressController{
		subscriber:          subscriber.GetInstance(),
		requestProgressView: views.RequestProgressViewGetInstance(),
	}
}

func (c *ShowRequestProgressController) Init() {
	c.subscriber.Subscribe(sharedenums.SharedEventEnum.API_REQUEST_STARTED, func(map[string]any) {
		c.requestProgressView.AddPendingRequest()
	})
	c.subscriber.Subscribe(sharedenums.SharedEventEnum.API_REQUEST_FINISHED, func(map[string]any) {
		c.requestProgressView.RemovePendingRequest()
	})
}
