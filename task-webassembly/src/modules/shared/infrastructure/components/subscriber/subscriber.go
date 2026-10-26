package subscriber

import (
	"syscall/js"

	"task-webassembly/src/modules/shared/application"
	"task-webassembly/src/modules/shared/infrastructure/components/jsvaluer"
)

type Subscriber struct {
	jsvaluer *jsvaluer.Jsvaluer
}

func GetInstance() *Subscriber {
	return &Subscriber{
		jsvaluer: jsvaluer.GetInstance(),
	}
}

// Subscribe onEvent recibe los primitivos que envio el Dispatcher. Corre en su propia goroutine: un controller
// que llama a la API espera (await) una Promise, y esperar dentro del callback de JS bloquearia el event loop.
func (s *Subscriber) Subscribe(eventName string, onEvent func(request map[string]any)) {
	listener := js.FuncOf(func(this js.Value, args []js.Value) any {
		request := application.ToMap(s.jsvaluer.GetGoValue(args[0].Get("detail").Get("request")))
		go onEvent(request)
		return nil
	})
	js.Global().Get("document").Call("addEventListener", eventName, listener)
}
