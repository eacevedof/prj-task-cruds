package views

import (
	"fmt"
	"strconv"
	"strings"
	"syscall/js"
	"time"

	"task-webassembly/src/modules/shared/infrastructure/components/logger"
	"task-webassembly/src/modules/shared/infrastructure/components/promiser"
	"task-webassembly/src/modules/shared/infrastructure/components/translator"
)

const (
	// solo se pintan las ultimas; la media y la copia al portapapeles son de todas las de la sesion de la pagina
	MAX_ROWS = 50
	// umbrales del color de cada tiempo (ms)
	FAST_MS = 300
	SLOW_MS = 1000
	// lo que dura el "Copiado" en el boton
	COPIED_FEEDBACK_MS = 1500
)

// RequestTime una llamada a la API ya terminada. FinishedAt: un Date de JS (la hora se pinta en la zona del navegador).
type RequestTime struct {
	OperationName string
	DurationMs    int
	IsOk          bool
	FinishedAt    js.Value
}

type requestTimesSummary struct {
	count     int
	averageMs *int
	lastMs    *int
}

type requestTimesElements struct {
	spanBackend    js.Value
	spanCount      js.Value
	spanAverage    js.Value
	spanLast       js.Value
	pEmpty         js.Value
	olRequestTimes js.Value
	buttonCopy     js.Value
	buttonClear    js.Value
}

// RequestTimesView footer fijo con el tiempo de respuesta de cada llamada a la API, la mas reciente arriba, el back
// activo y el resumen (cuantas, media, la ultima). Copia todo el historico al portapapeles (texto con tabuladores:
// se pega bien en una hoja de calculo). Las etiquetas son data-i18n del html: aqui solo se pintan numeros y nombres,
// asi que no hay que repintar al cambiar de idioma.
type RequestTimesView struct {
	logger         *logger.Logger
	promiser       *promiser.Promiser
	translator     *translator.Translator
	elements       requestTimesElements
	requestTimes   []RequestTime
	backendName    string
	backendBaseUrl string
}

var requestTimesViewInstance *RequestTimesView

func RequestTimesViewGetInstance() *RequestTimesView {
	if requestTimesViewInstance == nil {
		requestTimesViewInstance = &RequestTimesView{
			logger:     logger.GetInstance(),
			promiser:   promiser.GetInstance(),
			translator: translator.GetInstance(),
		}
	}
	return requestTimesViewInstance
}

func (v *RequestTimesView) Init() {
	document := js.Global().Get("document")
	v.elements = requestTimesElements{
		spanBackend:    document.Call("getElementById", "span-request-times-backend"),
		spanCount:      document.Call("getElementById", "span-request-times-count"),
		spanAverage:    document.Call("getElementById", "span-request-times-average"),
		spanLast:       document.Call("getElementById", "span-request-times-last"),
		pEmpty:         document.Call("getElementById", "p-request-times-empty"),
		olRequestTimes: document.Call("getElementById", "ol-request-times"),
		buttonCopy:     document.Call("getElementById", "button-copy-request-times"),
		buttonClear:    document.Call("getElementById", "button-clear-request-times"),
	}
	// los botones viven en el summary: sin preventDefault tambien plegarian/desplegarian el footer
	v.elements.buttonCopy.Call("addEventListener", "click", js.FuncOf(func(this js.Value, args []js.Value) any {
		args[0].Call("preventDefault")
		v.copyToClipboard()
		return nil
	}))
	v.elements.buttonClear.Call("addEventListener", "click", js.FuncOf(func(this js.Value, args []js.Value) any {
		args[0].Call("preventDefault")
		v.clear()
		return nil
	}))
	v.renderSummary()
}

// RenderBackend el back contra el que se miden los tiempos (cambiarlo recarga la pagina: el historico es de uno solo).
func (v *RequestTimesView) RenderBackend(name string, apiBaseUrl string) {
	v.backendName = name
	v.backendBaseUrl = apiBaseUrl
	v.elements.spanBackend.Set("textContent", name)
	v.elements.spanBackend.Set("title", apiBaseUrl)
}

func (v *RequestTimesView) AddRequestTime(requestTime RequestTime) {
	v.requestTimes = append(v.requestTimes, requestTime)

	v.elements.olRequestTimes.Call("prepend", v.createLiRequestTime(requestTime))
	for v.elements.olRequestTimes.Get("children").Length() > MAX_ROWS {
		v.elements.olRequestTimes.Get("lastElementChild").Call("remove")
	}
	v.elements.pEmpty.Get("classList").Call("add", CSS_HIDDEN)
	v.renderSummary()
}

func (v *RequestTimesView) clear() {
	v.requestTimes = nil
	v.elements.olRequestTimes.Call("replaceChildren")
	v.elements.pEmpty.Get("classList").Call("remove", CSS_HIDDEN)
	v.renderSummary()
}

func (v *RequestTimesView) copyToClipboard() {
	clipboard := js.Global().Get("navigator").Get("clipboard")
	if clipboard.IsUndefined() {
		return
	}
	v.promiser.Then(
		clipboard.Call("writeText", v.getClipboardText()),
		func(js.Value) {
			copyText := v.elements.buttonCopy.Get("textContent").String()
			v.elements.buttonCopy.Set("textContent", v.translator.GetText("request_times.copied", nil))
			time.AfterFunc(COPIED_FEEDBACK_MS*time.Millisecond, func() {
				v.elements.buttonCopy.Set("textContent", copyText)
			})
		},
		func(err error) {
			v.logger.LogError("RequestTimesView.copyToClipboard", err)
		},
	)
}

// getClipboardText cabecera (back, peticiones, media, ultima) y una linea por llamada, de la mas antigua a la mas nueva.
func (v *RequestTimesView) getClipboardText() string {
	summary := v.getSummary()
	lines := []string{
		v.translator.GetText("request_times.title", nil),
		v.translator.GetText("nav.backend", nil) + "\t" + v.backendName + "\t" + v.backendBaseUrl,
		v.translator.GetText("request_times.count", nil) + "\t" + strconv.Itoa(summary.count),
		v.translator.GetText("request_times.average", nil) + "\t" + v.getMsText(summary.averageMs),
		v.translator.GetText("request_times.last", nil) + "\t" + v.getMsText(summary.lastMs),
		"",
		strings.Join([]string{"#", "time", "operation", "ms", "status"}, "\t"),
	}
	for index, requestTime := range v.requestTimes {
		status := "error"
		if requestTime.IsOk {
			status = "ok"
		}
		lines = append(lines, strings.Join([]string{
			strconv.Itoa(index + 1),
			v.getClockTime(requestTime.FinishedAt),
			requestTime.OperationName,
			strconv.Itoa(requestTime.DurationMs),
			status,
		}, "\t"))
	}
	return strings.Join(lines, "\n")
}

func (v *RequestTimesView) renderSummary() {
	summary := v.getSummary()
	v.elements.spanCount.Set("textContent", strconv.Itoa(summary.count))
	v.elements.spanAverage.Set("textContent", v.getMsText(summary.averageMs))
	v.elements.spanLast.Set("textContent", v.getMsText(summary.lastMs))
	v.elements.buttonCopy.Set("disabled", summary.count == 0)
}

func (v *RequestTimesView) getSummary() requestTimesSummary {
	count := len(v.requestTimes)
	if count == 0 {
		return requestTimesSummary{count: count}
	}

	totalMs := 0
	for _, requestTime := range v.requestTimes {
		totalMs += requestTime.DurationMs
	}
	averageMs := int(float64(totalMs)/float64(count) + 0.5)
	lastMs := v.requestTimes[count-1].DurationMs
	return requestTimesSummary{count: count, averageMs: &averageMs, lastMs: &lastMs}
}

// getMsText durationMs nil: "-"
func (v *RequestTimesView) getMsText(durationMs *int) string {
	if durationMs == nil {
		return "-"
	}
	return fmt.Sprintf("%d ms", *durationMs)
}

func (v *RequestTimesView) createLiRequestTime(requestTime RequestTime) js.Value {
	document := js.Global().Get("document")
	liRequestTime := document.Call("createElement", "li")
	liRequestTime.Get("classList").Call("toggle", "request-time-error", !requestTime.IsOk)

	timeFinishedAt := document.Call("createElement", "time")
	timeFinishedAt.Set("dateTime", requestTime.FinishedAt.Call("toISOString").String())
	timeFinishedAt.Set("textContent", v.getClockTime(requestTime.FinishedAt))

	spanOperation := document.Call("createElement", "span")
	spanOperation.Set("className", "request-time-operation")
	spanOperation.Set("textContent", requestTime.OperationName)

	spanDuration := document.Call("createElement", "span")
	spanDuration.Set("className", "request-time-duration "+v.getSpeedCss(requestTime.DurationMs))
	spanDuration.Set("textContent", fmt.Sprintf("%d ms", requestTime.DurationMs))

	liRequestTime.Call("append", timeFinishedAt, spanOperation, spanDuration)
	return liRequestTime
}

func (v *RequestTimesView) getSpeedCss(durationMs int) string {
	if durationMs < FAST_MS {
		return "speed-fast"
	}
	if durationMs < SLOW_MS {
		return "speed-medium"
	}
	return "speed-slow"
}

// getClockTime HH:MM:SS local
func (v *RequestTimesView) getClockTime(date js.Value) string {
	return fmt.Sprintf("%02d:%02d:%02d", date.Call("getHours").Int(), date.Call("getMinutes").Int(), date.Call("getSeconds").Int())
}
