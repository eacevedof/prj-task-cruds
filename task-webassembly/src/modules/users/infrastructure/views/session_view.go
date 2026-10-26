package views

import (
	"syscall/js"

	"task-webassembly/src/modules/shared/application"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/users/domain/enums"
)

type sessionElements struct {
	divSession      js.Value
	spanSessionUser js.Value
	buttonLogOut    js.Value
}

// SessionView cabecera con la sesion abierta: quien es el usuario y el boton de salir.
type SessionView struct {
	dispatcher *dispatcher.Dispatcher
	elements   sessionElements
}

var sessionViewInstance *SessionView

func SessionViewGetInstance() *SessionView {
	if sessionViewInstance == nil {
		sessionViewInstance = &SessionView{
			dispatcher: dispatcher.GetInstance(),
		}
	}
	return sessionViewInstance
}

func (v *SessionView) Init() {
	document := js.Global().Get("document")
	v.elements = sessionElements{
		divSession:      document.Call("getElementById", "div-session"),
		spanSessionUser: document.Call("getElementById", "span-session-user"),
		buttonLogOut:    document.Call("getElementById", "button-log-out"),
	}
	v.elements.buttonLogOut.Call("addEventListener", "click", js.FuncOf(func(this js.Value, args []js.Value) any {
		v.dispatcher.Dispatch(enums.UsersEventEnum.LOG_OUT_REQUESTED, nil)
		return nil
	}))
}

// Show user: {userName, userEmail}
func (v *SessionView) Show(user map[string]any) {
	v.RenderUser(user)
	v.elements.divSession.Get("classList").Call("remove", CSS_HIDDEN)
}

func (v *SessionView) Hide() {
	v.elements.divSession.Get("classList").Call("add", CSS_HIDDEN)
	v.elements.spanSessionUser.Set("textContent", "")
}

// RenderUser user: {userName, userEmail}
func (v *SessionView) RenderUser(user map[string]any) {
	v.elements.spanSessionUser.Set("textContent", application.ToString(user["userName"])+" · "+application.ToString(user["userEmail"]))
}
