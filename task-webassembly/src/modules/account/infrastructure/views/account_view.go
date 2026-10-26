package views

import (
	"strings"
	"syscall/js"

	"task-webassembly/src/modules/account/domain/enums"
	"task-webassembly/src/modules/shared/application"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/htmler"
	"task-webassembly/src/modules/shared/infrastructure/components/promiser"
	"task-webassembly/src/modules/shared/infrastructure/components/translator"
)

const (
	CSS_HIDDEN = "hidden"
	CSS_ERROR  = "message-error"
)

type accountElements struct {
	sectionAccount            js.Value
	divAccountMessage         js.Value
	formAccount               js.Value
	inputAccountUserName      js.Value
	inputAccountUserEmail     js.Value
	buttonRegenerateApiToken  js.Value
	divAccountApiToken        js.Value
	codeAccountApiToken       js.Value
	buttonCopyAccountApiToken js.Value
	buttonDeleteAccount       js.Value
}

// AccountView pantalla "Cuenta": modificar datos, rotar el api_token y borrar la cuenta.
type AccountView struct {
	dispatcher *dispatcher.Dispatcher
	htmler     *htmler.Htmler
	promiser   *promiser.Promiser
	translator *translator.Translator
	elements   accountElements
}

var accountViewInstance *AccountView

func AccountViewGetInstance() *AccountView {
	if accountViewInstance == nil {
		accountViewInstance = &AccountView{
			dispatcher: dispatcher.GetInstance(),
			htmler:     htmler.GetInstance(),
			promiser:   promiser.GetInstance(),
			translator: translator.GetInstance(),
		}
	}
	return accountViewInstance
}

func (v *AccountView) Init() {
	document := js.Global().Get("document")
	v.elements = accountElements{
		sectionAccount:            document.Call("getElementById", "section-account"),
		divAccountMessage:         document.Call("getElementById", "div-account-message"),
		formAccount:               document.Call("getElementById", "form-account"),
		inputAccountUserName:      document.Call("getElementById", "input-account-user-name"),
		inputAccountUserEmail:     document.Call("getElementById", "input-account-user-email"),
		buttonRegenerateApiToken:  document.Call("getElementById", "button-regenerate-api-token"),
		divAccountApiToken:        document.Call("getElementById", "div-account-api-token"),
		codeAccountApiToken:       document.Call("getElementById", "code-account-api-token"),
		buttonCopyAccountApiToken: document.Call("getElementById", "button-copy-account-api-token"),
		buttonDeleteAccount:       document.Call("getElementById", "button-delete-account"),
	}
	v.elements.formAccount.Call("addEventListener", "submit", js.FuncOf(func(this js.Value, args []js.Value) any {
		v.onAccountSubmit(args[0])
		return nil
	}))
	v.elements.buttonRegenerateApiToken.Call("addEventListener", "click", js.FuncOf(func(this js.Value, args []js.Value) any {
		v.onRegenerateApiTokenClick()
		return nil
	}))
	v.elements.buttonCopyAccountApiToken.Call("addEventListener", "click", js.FuncOf(func(this js.Value, args []js.Value) any {
		v.onCopyAccountApiTokenClick()
		return nil
	}))
	v.elements.buttonDeleteAccount.Call("addEventListener", "click", js.FuncOf(func(this js.Value, args []js.Value) any {
		v.onDeleteAccountClick()
		return nil
	}))
}

// Reset al entrar o salir: sin avisos ni token a la vista.
func (v *AccountView) Reset() {
	v.RenderBusy(false)
	v.renderMessage("", false)
	v.elements.divAccountApiToken.Get("classList").Call("add", CSS_HIDDEN)
	v.elements.codeAccountApiToken.Set("textContent", "")
}

// RenderUser user: {userName, userEmail}
func (v *AccountView) RenderUser(user map[string]any) {
	v.elements.inputAccountUserName.Set("value", application.ToString(user["userName"]))
	v.elements.inputAccountUserEmail.Set("value", application.ToString(user["userEmail"]))
}

func (v *AccountView) RenderBusy(isBusy bool) {
	buttons := v.elements.sectionAccount.Call("querySelectorAll", "button")
	for i := 0; i < buttons.Length(); i++ {
		buttons.Index(i).Set("disabled", isBusy)
	}
}

// RenderSuccess textKey: AccountTextEnum
func (v *AccountView) RenderSuccess(textKey string) {
	v.RenderBusy(false)
	v.renderMessage(v.translator.GetText(textKey, nil), false)
}

func (v *AccountView) RenderErrors(errors []string) {
	v.RenderBusy(false)
	v.renderMessage(strings.Join(errors, "\n"), true)
}

// RenderNewApiToken apiToken: el nuevo, que solo se ve ahora
func (v *AccountView) RenderNewApiToken(apiToken string) {
	v.RenderBusy(false)
	v.renderMessage("", false)
	v.elements.codeAccountApiToken.Set("textContent", apiToken)
	v.elements.buttonCopyAccountApiToken.Set("textContent", v.translator.GetText(enums.AccountTextEnum.COPY, nil))
	v.elements.divAccountApiToken.Get("classList").Call("remove", CSS_HIDDEN)
}

func (v *AccountView) onAccountSubmit(event js.Value) {
	event.Call("preventDefault")
	v.RenderBusy(true)
	v.dispatcher.Dispatch(enums.AccountEventEnum.UPDATE_ACCOUNT_REQUESTED, map[string]any{
		"userName":  v.elements.inputAccountUserName.Get("value").String(),
		"userEmail": v.elements.inputAccountUserEmail.Get("value").String(),
	})
}

func (v *AccountView) onRegenerateApiTokenClick() {
	if !js.Global().Call("confirm", v.translator.GetText(enums.AccountTextEnum.CONFIRM_REGENERATE_API_TOKEN, nil)).Bool() {
		return
	}
	v.RenderBusy(true)
	v.dispatcher.Dispatch(enums.AccountEventEnum.REGENERATE_API_TOKEN_REQUESTED, nil)
}

func (v *AccountView) onCopyAccountApiTokenClick() {
	clipboard := js.Global().Get("navigator").Get("clipboard")
	if clipboard.IsUndefined() {
		return
	}
	v.promiser.Then(
		clipboard.Call("writeText", v.elements.codeAccountApiToken.Get("textContent")),
		func(js.Value) {
			v.elements.buttonCopyAccountApiToken.Set("textContent", v.translator.GetText(enums.AccountTextEnum.COPIED, nil))
		},
		nil,
	)
}

func (v *AccountView) onDeleteAccountClick() {
	if !js.Global().Call("confirm", v.translator.GetText(enums.AccountTextEnum.CONFIRM_DELETE, nil)).Bool() {
		return
	}
	v.RenderBusy(true)
	v.dispatcher.Dispatch(enums.AccountEventEnum.DELETE_ACCOUNT_REQUESTED, nil)
}

func (v *AccountView) renderMessage(message string, isError bool) {
	v.elements.divAccountMessage.Set("innerHTML", strings.ReplaceAll(v.htmler.GetEscaped(message), "\n", "<br>"))
	v.elements.divAccountMessage.Get("classList").Call("toggle", CSS_HIDDEN, message == "")
	v.elements.divAccountMessage.Get("classList").Call("toggle", CSS_ERROR, isError)
}
