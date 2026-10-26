package controllers

import (
	"math"
	"syscall/js"

	"task-webassembly/src/modules/shared/application"
	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/subscriber"
	"task-webassembly/src/modules/shared/infrastructure/views"
)

// ShowRequestTimesController tiempo de respuesta de cada llamada a la API en el footer: el GraphqlRequester lo mide y
// lo manda al terminar. No hay caso de uso: es solo interfaz y no se guarda (se pierde al recargar).
type ShowRequestTimesController struct {
	subscriber       *subscriber.Subscriber
	requestTimesView *views.RequestTimesView
}

func ShowRequestTimesControllerGetInstance() *ShowRequestTimesController {
	return &ShowRequestTimesController{
		subscriber:       subscriber.GetInstance(),
		requestTimesView: views.RequestTimesViewGetInstance(),
	}
}

func (c *ShowRequestTimesController) Init() {
	c.subscriber.Subscribe(sharedenums.SharedEventEnum.API_REQUEST_FINISHED, c.Invoke)
}

// Invoke request: {operationName, durationMs, isOk}
func (c *ShowRequestTimesController) Invoke(request map[string]any) {
	durationMs, _ := request["durationMs"].(float64)
	isOk, _ := request["isOk"].(bool)
	c.requestTimesView.AddRequestTime(views.RequestTime{
		OperationName: application.ToString(request["operationName"]),
		DurationMs:    int(math.Round(durationMs)),
		IsOk:          isOk,
		FinishedAt:    js.Global().Get("Date").New(),
	})
}
