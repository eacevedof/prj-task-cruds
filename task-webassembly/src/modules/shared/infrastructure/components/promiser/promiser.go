package promiser

import (
	"errors"
	"syscall/js"
)

// Promiser el await de task-js: espera a que una Promise de JS (fetch, response.text()...) se resuelva.
// Bloquea la goroutine que lo llama, asi que NUNCA se usa dentro de un callback de JS (js.FuncOf): bloquearia el
// event loop y la Promise no se resolveria nunca. Por eso el Subscriber lanza cada controller en su goroutine.
type Promiser struct{}

func GetInstance() *Promiser {
	return &Promiser{}
}

// Await devuelve el valor de la Promise o, si se rechaza, su motivo como error.
func (p *Promiser) Await(promise js.Value) (js.Value, error) {
	resolved := make(chan js.Value, 1)
	rejected := make(chan js.Value, 1)

	onResolve := js.FuncOf(func(this js.Value, args []js.Value) any {
		resolved <- p.getFirstArg(args)
		return nil
	})
	defer onResolve.Release()
	onReject := js.FuncOf(func(this js.Value, args []js.Value) any {
		rejected <- p.getFirstArg(args)
		return nil
	})
	defer onReject.Release()

	promise.Call("then", onResolve, onReject)
	select {
	case value := <-resolved:
		return value, nil
	case reason := <-rejected:
		return js.Undefined(), p.getRejectionError(reason)
	}
}

// Then el promise.then(...).catch(...) de task-js: no bloquea, asi que vale dentro de un callback de JS (un click
// que copia al portapapeles). onReject puede ser nil.
func (p *Promiser) Then(promise js.Value, onResolve func(value js.Value), onReject func(err error)) {
	var onResolveFunc, onRejectFunc js.Func
	release := func() {
		onResolveFunc.Release()
		onRejectFunc.Release()
	}
	onResolveFunc = js.FuncOf(func(this js.Value, args []js.Value) any {
		defer release()
		onResolve(p.getFirstArg(args))
		return nil
	})
	onRejectFunc = js.FuncOf(func(this js.Value, args []js.Value) any {
		defer release()
		if onReject != nil {
			onReject(p.getRejectionError(p.getFirstArg(args)))
		}
		return nil
	})
	promise.Call("then", onResolveFunc, onRejectFunc)
}

func (p *Promiser) getFirstArg(args []js.Value) js.Value {
	if len(args) == 0 {
		return js.Undefined()
	}
	return args[0]
}

// getRejectionError un Error de JS trae message (TypeError: Failed to fetch); si no, el valor tal cual.
func (p *Promiser) getRejectionError(reason js.Value) error {
	if reason.Type() == js.TypeObject && reason.Get("message").Type() == js.TypeString {
		return errors.New(reason.Get("message").String())
	}
	return errors.New(js.Global().Get("String").Invoke(reason).String())
}
