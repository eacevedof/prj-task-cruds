package views

import (
	"strings"
	"syscall/js"

	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/htmler"
	"task-webassembly/src/modules/shared/infrastructure/components/translator"
	"task-webassembly/src/modules/users/domain/enums"
)

const (
	CSS_HIDDEN = "hidden"
	CSS_ERROR  = "message-error"
)

type authElements struct {
	sectionAuth          js.Value
	divAuthMessage       js.Value
	formLogIn            js.Value
	inputLogInUserName   js.Value
	inputLogInPassword   js.Value
	formSignUp           js.Value
	inputSignUpUserName  js.Value
	inputSignUpUserEmail js.Value
	inputSignUpPassword  js.Value
}

// AuthView pantalla de entrada: login con user_name + password o crear cuenta (que entra directamente).
// Una sola instancia: guarda las referencias al DOM.
type AuthView struct {
	dispatcher *dispatcher.Dispatcher
	htmler     *htmler.Htmler
	translator *translator.Translator
	elements   authElements
}

var authViewInstance *AuthView

func AuthViewGetInstance() *AuthView {
	if authViewInstance == nil {
		authViewInstance = &AuthView{
			dispatcher: dispatcher.GetInstance(),
			htmler:     htmler.GetInstance(),
			translator: translator.GetInstance(),
		}
	}
	return authViewInstance
}

func (v *AuthView) Init() {
	v.loadElements()
	v.elements.formLogIn.Call("addEventListener", "submit", js.FuncOf(func(this js.Value, args []js.Value) any {
		v.onLogInSubmit(args[0])
		return nil
	}))
	v.elements.formSignUp.Call("addEventListener", "submit", js.FuncOf(func(this js.Value, args []js.Value) any {
		v.onSignUpSubmit(args[0])
		return nil
	}))
}

// Show messageKey: aviso opcional (UsersTextEnum): sesion caducada, cuenta borrada... ("" = sin aviso)
func (v *AuthView) Show(messageKey string) {
	v.elements.sectionAuth.Get("classList").Call("remove", CSS_HIDDEN)
	v.elements.formLogIn.Call("reset")
	v.elements.formSignUp.Call("reset")
	v.RenderBusy(false)
	message := ""
	if messageKey != "" {
		message = v.translator.GetText(messageKey, nil)
	}
	v.renderMessage(message, false)
	v.elements.inputLogInUserName.Call("focus")
}

func (v *AuthView) Hide() {
	v.elements.sectionAuth.Get("classList").Call("add", CSS_HIDDEN)
	v.renderMessage("", false)
	// la password no se queda en el DOM al entrar
	v.elements.formLogIn.Call("reset")
	v.elements.formSignUp.Call("reset")
}

func (v *AuthView) RenderBusy(isBusy bool) {
	buttons := v.elements.sectionAuth.Call("querySelectorAll", "button")
	for i := 0; i < buttons.Length(); i++ {
		buttons.Index(i).Set("disabled", isBusy)
	}
}

func (v *AuthView) RenderErrors(errors []string) {
	// al restaurar una sesion la pantalla esta oculta: si falla hay que volver a ella
	v.elements.sectionAuth.Get("classList").Call("remove", CSS_HIDDEN)
	v.RenderBusy(false)
	v.renderMessage(strings.Join(errors, "\n"), true)
}

func (v *AuthView) loadElements() {
	document := js.Global().Get("document")
	v.elements = authElements{
		sectionAuth:          document.Call("getElementById", "section-auth"),
		divAuthMessage:       document.Call("getElementById", "div-auth-message"),
		formLogIn:            document.Call("getElementById", "form-log-in"),
		inputLogInUserName:   document.Call("getElementById", "input-log-in-user-name"),
		inputLogInPassword:   document.Call("getElementById", "input-log-in-password"),
		formSignUp:           document.Call("getElementById", "form-sign-up"),
		inputSignUpUserName:  document.Call("getElementById", "input-sign-up-user-name"),
		inputSignUpUserEmail: document.Call("getElementById", "input-sign-up-user-email"),
		inputSignUpPassword:  document.Call("getElementById", "input-sign-up-password"),
	}
}

func (v *AuthView) onLogInSubmit(event js.Value) {
	event.Call("preventDefault")
	v.RenderBusy(true)
	v.dispatcher.Dispatch(enums.UsersEventEnum.LOG_IN_REQUESTED, map[string]any{
		"userName": v.elements.inputLogInUserName.Get("value").String(),
		"password": v.elements.inputLogInPassword.Get("value").String(),
	})
}

func (v *AuthView) onSignUpSubmit(event js.Value) {
	event.Call("preventDefault")
	v.RenderBusy(true)
	v.dispatcher.Dispatch(enums.UsersEventEnum.SIGN_UP_REQUESTED, map[string]any{
		"userName":  v.elements.inputSignUpUserName.Get("value").String(),
		"userEmail": v.elements.inputSignUpUserEmail.Get("value").String(),
		"password":  v.elements.inputSignUpPassword.Get("value").String(),
	})
}

func (v *AuthView) renderMessage(message string, isError bool) {
	v.elements.divAuthMessage.Set("innerHTML", strings.ReplaceAll(v.htmler.GetEscaped(message), "\n", "<br>"))
	v.elements.divAuthMessage.Get("classList").Call("toggle", CSS_HIDDEN, message == "")
	v.elements.divAuthMessage.Get("classList").Call("toggle", CSS_ERROR, isError)
}
