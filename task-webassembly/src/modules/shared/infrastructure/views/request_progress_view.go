package views

import "syscall/js"

type requestProgressElements struct {
	dialogRequestProgress js.Value
}

// RequestProgressView modal con barra de progreso mientras haya alguna llamada a la API en curso. Lleva la cuenta de
// las pendientes: con dos a la vez se cierra al acabar la ultima, no la primera.
type RequestProgressView struct {
	elements        requestProgressElements
	pendingRequests int
}

var requestProgressViewInstance *RequestProgressView

func RequestProgressViewGetInstance() *RequestProgressView {
	if requestProgressViewInstance == nil {
		requestProgressViewInstance = &RequestProgressView{}
	}
	return requestProgressViewInstance
}

func (v *RequestProgressView) Init() {
	v.elements = requestProgressElements{
		dialogRequestProgress: js.Global().Get("document").Call("getElementById", "dialog-request-progress"),
	}
	// Esc cerraria el modal con la peticion aun en vuelo
	v.elements.dialogRequestProgress.Call("addEventListener", "cancel", js.FuncOf(func(this js.Value, args []js.Value) any {
		args[0].Call("preventDefault")
		return nil
	}))
}

func (v *RequestProgressView) AddPendingRequest() {
	v.pendingRequests++
	if v.pendingRequests > 1 {
		return
	}

	// <dialog>: modal nativo (el resto de la pagina queda inerte); sin soporte, al menos visible
	if v.elements.dialogRequestProgress.Get("showModal").Type() == js.TypeFunction {
		v.elements.dialogRequestProgress.Call("showModal")
		return
	}
	v.elements.dialogRequestProgress.Call("setAttribute", "open", "")
}

func (v *RequestProgressView) RemovePendingRequest() {
	v.pendingRequests = max(0, v.pendingRequests-1)
	if v.pendingRequests > 0 {
		return
	}

	if v.elements.dialogRequestProgress.Get("close").Type() == js.TypeFunction {
		v.elements.dialogRequestProgress.Call("close")
		return
	}
	v.elements.dialogRequestProgress.Call("removeAttribute", "open")
}
