package dispatcher

import (
	"syscall/js"

	"task-webassembly/src/modules/shared/infrastructure/components/jsvaluer"
)

// Dispatcher bus de eventos de la SPA sobre document: la vista dispara, el controller (Subscriber) escucha.
type Dispatcher struct {
	jsvaluer *jsvaluer.Jsvaluer
}

func GetInstance() *Dispatcher {
	return &Dispatcher{
		jsvaluer: jsvaluer.GetInstance(),
	}
}

// Dispatch request: primitivos que recibira el controller (nil = {}).
func (d *Dispatcher) Dispatch(eventName string, request map[string]any) {
	if request == nil {
		request = map[string]any{}
	}
	eventInit := d.jsvaluer.GetJsValue(map[string]any{
		"detail": map[string]any{"request": request},
	})
	document := js.Global().Get("document")
	document.Call("dispatchEvent", js.Global().Get("CustomEvent").New(eventName, eventInit))
}
