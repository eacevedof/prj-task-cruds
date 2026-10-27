package views

import (
	"slices"
	"strings"
	"syscall/js"

	"task-webassembly/src/modules/shared/application"
	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/jsvaluer"
	"task-webassembly/src/modules/shared/infrastructure/components/translator"
)

const (
	CSS_HIDDEN        = "hidden"
	CSS_ACTIVE        = "active"
	BACKEND_LOGO_PATH = "/img/backends"
	// donde publica cada back su documentacion (la misma ruta en php, go, rust, nodejs y java)
	API_DOCUMENTATION_PATH = "/api/v1"
)

type navigationElements struct {
	navScreens        js.Value
	screenLinks       []js.Value
	screens           []js.Value
	selectLanguage    js.Value
	divBackends       js.Value
	aApiDocumentation js.Value
}

// NavigationView menu de pantallas (por el hash de la url: #tasks, #account), selector de back y de idioma. Cada
// pantalla es una section con data-screen; solo se ve la del hash y solo con sesion (Show/Hide).
type NavigationView struct {
	dispatcher *dispatcher.Dispatcher
	jsvaluer   *jsvaluer.Jsvaluer
	translator *translator.Translator
	elements   navigationElements
	isVisible  bool
}

var navigationViewInstance *NavigationView

func NavigationViewGetInstance() *NavigationView {
	if navigationViewInstance == nil {
		navigationViewInstance = &NavigationView{
			dispatcher: dispatcher.GetInstance(),
			jsvaluer:   jsvaluer.GetInstance(),
			translator: translator.GetInstance(),
		}
	}
	return navigationViewInstance
}

func (v *NavigationView) Init() {
	document := js.Global().Get("document")
	v.elements = navigationElements{
		navScreens:        document.Call("getElementById", "nav-screens"),
		screenLinks:       v.jsvaluer.GetValues(document.Call("querySelectorAll", "#nav-screens a[data-screen]")),
		screens:           v.jsvaluer.GetValues(document.Call("querySelectorAll", "section[data-screen]")),
		selectLanguage:    document.Call("getElementById", "select-language"),
		divBackends:       document.Call("getElementById", "div-backends"),
		aApiDocumentation: document.Call("getElementById", "a-api-documentation"),
	}
	js.Global().Call("addEventListener", "hashchange", js.FuncOf(func(this js.Value, args []js.Value) any {
		v.renderCurrentScreen()
		return nil
	}))
	v.elements.selectLanguage.Call("addEventListener", "change", js.FuncOf(func(this js.Value, args []js.Value) any {
		v.dispatcher.Dispatch(sharedenums.SharedEventEnum.LANGUAGE_CHANGE_REQUESTED, map[string]any{
			"languageCode": v.elements.selectLanguage.Get("value").String(),
		})
		return nil
	}))
	v.elements.divBackends.Call("addEventListener", "click", js.FuncOf(func(this js.Value, args []js.Value) any {
		buttonBackend := args[0].Get("target").Call("closest", "button[data-backend-code]")
		if buttonBackend.IsNull() || buttonBackend.Call("getAttribute", "aria-checked").String() == "true" {
			return nil
		}
		v.dispatcher.Dispatch(sharedenums.SharedEventEnum.BACKEND_CHANGE_REQUESTED, map[string]any{
			"backendCode": buttonBackend.Get("dataset").Get("backendCode").String(),
		})
		return nil
	}))
}

// Show con sesion: menu visible y la pantalla del hash (o la de por defecto).
func (v *NavigationView) Show() {
	v.isVisible = true
	v.elements.navScreens.Get("classList").Call("remove", CSS_HIDDEN)
	v.renderCurrentScreen()
}

func (v *NavigationView) Hide() {
	v.isVisible = false
	v.elements.navScreens.Get("classList").Call("add", CSS_HIDDEN)
	for _, screen := range v.elements.screens {
		screen.Get("classList").Call("add", CSS_HIDDEN)
	}
}

// GoToScreen screen: ScreenEnum
func (v *NavigationView) GoToScreen(screen string) {
	location := js.Global().Get("location")
	if location.Get("hash").String() == "#"+screen {
		v.renderCurrentScreen()
		return
	}
	location.Set("hash", screen)
}

// RenderLanguage idioma ya fijado en el Translator: textos fijos del html, atributo lang y selector.
func (v *NavigationView) RenderLanguage() {
	languageCode := v.translator.GetLanguageCode()
	document := js.Global().Get("document")
	document.Get("documentElement").Set("lang", languageCode)
	v.elements.selectLanguage.Set("value", languageCode)
	v.translator.TranslateElement(document)
}

// RenderBackends logos de los backs y, al lado, el enlace a la documentacion de la API del activo.
// backends: los de /config.json ({code, name, apiBaseUrl}); backendCode: el activo.
func (v *NavigationView) RenderBackends(backends []map[string]any, backendCode string) {
	buttonsBackend := make([]any, 0, len(backends))
	for _, backend := range backends {
		buttonsBackend = append(buttonsBackend, v.createButtonBackend(backend, application.ToString(backend["code"]) == backendCode))
	}
	v.elements.divBackends.Call("replaceChildren", buttonsBackend...)

	activeBackendIndex := slices.IndexFunc(backends, func(backend map[string]any) bool {
		return application.ToString(backend["code"]) == backendCode
	})
	if activeBackendIndex < 0 {
		return
	}
	activeBackend := backends[activeBackendIndex]
	apiDocumentationUrl := application.ToString(activeBackend["apiBaseUrl"]) + API_DOCUMENTATION_PATH
	v.elements.aApiDocumentation.Set("href", apiDocumentationUrl)
	v.elements.aApiDocumentation.Set("title", application.ToString(activeBackend["name"])+": "+apiDocumentationUrl)
	v.elements.aApiDocumentation.Get("classList").Call("remove", CSS_HIDDEN)
}

// createButtonBackend boton con el logo del back (/img/backends/<code>.svg); sin logo se queda el nombre.
func (v *NavigationView) createButtonBackend(backend map[string]any, isActive bool) js.Value {
	document := js.Global().Get("document")
	code := application.ToString(backend["code"])
	name := application.ToString(backend["name"])

	buttonBackend := document.Call("createElement", "button")
	buttonBackend.Set("type", "button")
	buttonBackend.Get("dataset").Set("backendCode", code)
	buttonBackend.Set("title", name)
	buttonBackend.Call("setAttribute", "role", "radio")
	if isActive {
		buttonBackend.Call("setAttribute", "aria-checked", "true")
	} else {
		buttonBackend.Call("setAttribute", "aria-checked", "false")
	}
	buttonBackend.Call("setAttribute", "aria-label", name)

	imgLogo := document.Call("createElement", "img")
	imgLogo.Set("src", BACKEND_LOGO_PATH+"/"+code+".svg")
	imgLogo.Set("alt", "")
	var onLogoError js.Func
	onLogoError = js.FuncOf(func(this js.Value, args []js.Value) any {
		defer onLogoError.Release()
		imgLogo.Call("replaceWith", name)
		return nil
	})
	imgLogo.Call("addEventListener", "error", onLogoError, map[string]any{"once": true})
	buttonBackend.Call("append", imgLogo)
	return buttonBackend
}

// ReloadPage otro back: se arranca de cero contra el (sesion, listados y caches son del anterior).
func (v *NavigationView) ReloadPage() {
	js.Global().Get("location").Call("reload")
}

func (v *NavigationView) renderCurrentScreen() {
	if !v.isVisible {
		return
	}

	requestedScreen := strings.Replace(js.Global().Get("location").Get("hash").String(), "#", "", 1)
	currentScreen := sharedenums.ScreenEnum.DEFAULT
	if slices.Contains(sharedenums.ScreenEnum.VALUES, requestedScreen) {
		currentScreen = requestedScreen
	}
	for _, screen := range v.elements.screens {
		screen.Get("classList").Call("toggle", CSS_HIDDEN, screen.Get("dataset").Get("screen").String() != currentScreen)
	}
	for _, screenLink := range v.elements.screenLinks {
		screenLink.Get("classList").Call("toggle", CSS_ACTIVE, screenLink.Get("dataset").Get("screen").String() == currentScreen)
	}
}
