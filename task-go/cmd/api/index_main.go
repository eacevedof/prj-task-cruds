package main

import (
	"fmt"
	"net/http"
	"runtime/debug"
	"strings"

	"task-go/src/core/boot"
	"task-go/src/core/config"
	"task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
	"task-go/src/modules/shared/infrastructure/components/uuid"
	"task-go/src/modules/shared/infrastructure/controllers"
	"task-go/src/modules/shared/infrastructure/repositories/configuration"
)

// IndexMain front controller de la API (el public/index.php de task-php). Sin sesion ni vistas: solo JSON.
type IndexMain struct {
	routes []boot.Route
}

func IndexMainGetInstance(routes []boot.Route) *IndexMain {
	return &IndexMain{routes: routes}
}

func (m *IndexMain) ServeHTTP(responseWriter http.ResponseWriter, request *http.Request) {
	httpRequester := httprequester.GetInstance(request)
	defer func() {
		if recovered := recover(); recovered != nil {
			err := fmt.Errorf("%v", recovered)
			debugError(httpRequester, err)
			onError(httpRequester, err).EchoHeadersAndBody(responseWriter)
		}
	}()

	m.exec(httpRequester).EchoHeadersAndBody(responseWriter)
}

func (m *IndexMain) exec(httpRequester *httprequester.HttpRequester) *httpresponder.HttpResponder {
	requestMethod := httpRequester.GetRequestMethod()
	if requestMethod == "options" {
		return getWithCommonHeaders(httpRequester, httpresponder.NoContent())
	}

	runData := boot.ApiRouterGetInstance(m.routes).GetRunData(requestMethod, httpRequester.GetRequestUri())
	if runData.Route == nil && len(runData.Allowed) == 0 {
		return getWithCommonHeaders(httpRequester, getErrorResponse(
			enums.ExceptionEnum.CODE_NOT_FOUND,
			"Resource not found",
			map[string]string{},
		))
	}

	if runData.Route == nil {
		return getWithCommonHeaders(httpRequester, getErrorResponse(
			enums.ExceptionEnum.CODE_METHOD_NOT_ALLOWED,
			"Method not allowed",
			map[string]string{"Allow": strings.ToUpper(strings.Join(runData.Allowed, ", "))},
		))
	}

	httpRequester.LoadJsonBodyIntoPost()
	return getWithCommonHeaders(httpRequester, runData.Route.Controller(httpRequester, runData.Args))
}

// getWithCommonHeaders toda respuesta sale por aqui: las cabeceras comunes se suman a las del HttpResponder y se
// emiten juntas en EchoHeadersAndBody(), el unico punto que escribe la respuesta.
func getWithCommonHeaders(httpRequester *httprequester.HttpRequester, httpResponder *httpresponder.HttpResponder) *httpresponder.HttpResponder {
	commonHeaders := getCorsHeaders(httpRequester)
	for key, value := range getSecurityHeaders() {
		commonHeaders[key] = value
	}
	return httpResponder.GetWithHeaders(commonHeaders)
}

func getCorsHeaders(httpRequester *httprequester.HttpRequester) map[string]string {
	corsHeaders := map[string]string{}
	if origin := httpRequester.GetHeaderValueByKey("Origin"); origin != "" {
		corsHeaders["Access-Control-Allow-Origin"] = origin
		corsHeaders["Access-Control-Max-Age"] = "86400"
	}
	if httpRequester.GetRequestMethod() == "options" {
		corsHeaders["Access-Control-Allow-Methods"] = "GET, POST, PUT, DELETE, OPTIONS"
		corsHeaders["Access-Control-Allow-Headers"] = "Content-Type, Accept, Accept-Language, Authorization, X-Time-Zone"
	}
	return corsHeaders
}

func getSecurityHeaders() map[string]string {
	return map[string]string{
		"X-Content-Type-Options": "nosniff",
		"Referrer-Policy":        "no-referrer",
	}
}

// onError el panic que nadie ha tratado: se loguea con un id y el cliente recibe ese id en un 500 generico.
func onError(httpRequester *httprequester.HttpRequester, err error) *httpresponder.HttpResponder {
	errorUuid := uuid.GetInstance().GetUniqueIdWithPrefix("")
	sensitiveDataMasker := boot.SensitiveDataMaskerGetInstance()
	if post := httpRequester.GetPost(); len(post) > 0 {
		config.Lgerr(sensitiveDataMasker.GetMaskedArray(post), "index-exception "+errorUuid+" POST", "error")
	}
	if get := httpRequester.GetGet(); len(get) > 0 {
		config.Lgerr(sensitiveDataMasker.GetMaskedArray(get), "index-exception "+errorUuid+" GET", "error")
	}
	config.Lgerr(err.Error(), "index-exception "+errorUuid, "error")
	config.Lgerr(string(debug.Stack()), "index-exception "+errorUuid+" TRACE", "error")

	return getWithCommonHeaders(httpRequester, httpresponder.Json(
		controllers.GetJsonEncoded(controllers.JsonEnvelope{
			Code:   enums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
			Status: false,
			Errors: []string{"Sorry! but some unexpected error occurred (" + errorUuid + ")"},
			Data:   []any{},
		}),
		enums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
		map[string]string{},
	))
}

func debugError(httpRequester *httprequester.HttpRequester, err error) {
	environmentReaderRawRepository := configuration.GetInstance()
	if environmentReaderRawRepository.IsProduction() || !environmentReaderRawRepository.IsDebug() {
		return
	}

	config.Lgerr(map[string]any{
		"Exception": err.Error(),
		"POST":      httpRequester.GetPost(),
		"GET":       httpRequester.GetGet(),
	}, "index-debug", "debug")
}

func getErrorResponse(code int, errorMessage string, headers map[string]string) *httpresponder.HttpResponder {
	return httpresponder.Json(
		controllers.GetJsonEncoded(controllers.JsonEnvelope{
			Code:   code,
			Status: false,
			Errors: []string{errorMessage},
			Data:   []any{},
		}),
		code,
		headers,
	)
}
