package httpresponder

import (
	"maps"
	"net/http"

	"task-go/src/modules/shared/domain/enums"
)

// HttpResponder value object de TRANSPORTE HTTP: lo que un controller devuelve y el front controller
// (IndexMain) emite. NINGUN controller escribe en el http.ResponseWriter: construye un HttpResponder
// y lo devuelve (asi los tests pueden asertar GetStatusCode() sin emitir nada).
type HttpResponder struct {
	statusCode int
	body       string
	headers    map[string]string
}

// Json body es el JSON ya serializado.
func Json(body string, statusCode int, headers map[string]string) *HttpResponder {
	allHeaders := map[string]string{enums.HttpEnum.HEADER_CONTENT_TYPE: enums.HttpEnum.CONTENT_TYPE_JSON}
	maps.Copy(allHeaders, headers)
	return &HttpResponder{statusCode: statusCode, body: body, headers: allHeaders}
}

func Html(body string, statusCode int) *HttpResponder {
	return &HttpResponder{
		statusCode: statusCode,
		body:       body,
		headers:    map[string]string{enums.HttpEnum.HEADER_CONTENT_TYPE: enums.HttpEnum.CONTENT_TYPE_HTML},
	}
}

func NoContent() *HttpResponder {
	return &HttpResponder{statusCode: enums.ExceptionEnum.CODE_NO_CONTENT, body: "", headers: map[string]string{}}
}

func (h *HttpResponder) GetStatusCode() int {
	return h.statusCode
}

func (h *HttpResponder) GetBody() string {
	return h.body
}

func (h *HttpResponder) GetHeaders() map[string]string {
	return h.headers
}

// GetWithHeaders copia con cabeceras comunes (CORS, seguridad) que pone el front controller; si coinciden, gana la
// del controller.
func (h *HttpResponder) GetWithHeaders(headers map[string]string) *HttpResponder {
	allHeaders := maps.Clone(headers)
	maps.Copy(allHeaders, h.headers)
	return &HttpResponder{statusCode: h.statusCode, body: h.body, headers: allHeaders}
}

// EchoHeadersAndBody UNICO punto de emision de toda la app. Lo llama IndexMain.
func (h *HttpResponder) EchoHeadersAndBody(responseWriter http.ResponseWriter) {
	for key, value := range h.headers {
		responseWriter.Header().Set(key, value)
	}
	responseWriter.WriteHeader(h.statusCode)
	_, _ = responseWriter.Write([]byte(h.body))
}
