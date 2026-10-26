// Package components peticiones simuladas para los tests.
package components

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"

	"task-go/src/modules/shared/infrastructure/components/httprequester"
)

// HttpRequestMock peticion simulada: cabeceras (el Bearer que lee GetAuthUserOrFail, User-Agent / Accept-Language /
// X-Time-Zone que lee HttpRequester), ip, body JSON y query. GetMockedInstance() devuelve el HttpRequester ya con el
// body cargado (lo que hace IndexMain) y los tags de la url (":userUuid") para el controller.
type HttpRequestMock struct {
	bearerToken string
	ipAddress   string
	headers     map[string]string
	routeParams map[string]string
	query       map[string]string
	postRaw     map[string]any
}

func GetInstance() *HttpRequestMock {
	return &HttpRequestMock{headers: map[string]string{}, routeParams: map[string]string{}, query: map[string]string{}}
}

func (m *HttpRequestMock) SetBearerToken(bearerToken string) *HttpRequestMock {
	m.bearerToken = bearerToken
	return m
}

func (m *HttpRequestMock) SetIpAddress(ipAddress string) *HttpRequestMock {
	m.ipAddress = ipAddress
	return m
}

// SetHeaders cabeceras con su nombre HTTP: {"User-Agent": "...", "Accept-Language": "es-ES"}.
func (m *HttpRequestMock) SetHeaders(headers map[string]string) *HttpRequestMock {
	m.headers = headers
	return m
}

func (m *HttpRequestMock) SetRouteParams(routeParams map[string]string) *HttpRequestMock {
	m.routeParams = routeParams
	return m
}

func (m *HttpRequestMock) SetGetParams(queryParams map[string]string) *HttpRequestMock {
	m.query = queryParams
	return m
}

func (m *HttpRequestMock) SetPostRawParams(postParams map[string]any) *HttpRequestMock {
	m.postRaw = postParams
	return m
}

// GetMockedInstance el HttpRequester de la peticion y los argumentos con nombre del controller.
func (m *HttpRequestMock) GetMockedInstance() (*httprequester.HttpRequester, map[string]string) {
	body, _ := json.Marshal(m.postRaw)
	if m.postRaw == nil {
		body = nil
	}
	request := httptest.NewRequest(http.MethodPost, "/", bytes.NewReader(body))
	request.Header.Set("Content-Type", "application/json")
	request.RemoteAddr = m.ipAddress
	if m.bearerToken != "" {
		request.Header.Set("Authorization", "Bearer "+m.bearerToken)
	}
	for headerName, headerValue := range m.headers {
		request.Header.Set(headerName, headerValue)
	}
	queryValues := request.URL.Query()
	for key, value := range m.query {
		queryValues.Set(key, value)
	}
	request.URL.RawQuery = queryValues.Encode()

	httpRequester := httprequester.GetInstance(request)
	httpRequester.LoadJsonBodyIntoPost()
	return httpRequester, m.routeParams
}
