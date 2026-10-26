package graphqlrequester

import (
	"encoding/json"
	"errors"
	"regexp"
	"strings"
	"syscall/js"

	"task-webassembly/src/modules/shared/application"
	sharedenums "task-webassembly/src/modules/shared/domain/enums"
	"task-webassembly/src/modules/shared/infrastructure/components/dispatcher"
	"task-webassembly/src/modules/shared/infrastructure/components/jsvaluer"
	"task-webassembly/src/modules/shared/infrastructure/components/promiser"
)

const (
	GRAPHQL_PATH = "/graphql"
	CODE_OK      = 200
	// error de GraphQL sin extensions.code (consulta mal formada, campo inexistente): es un fallo del cliente
	CODE_GRAPHQL_ERROR = 400
)

var trailingSlashesRegex = regexp.MustCompile(`/+$`)

// ApiResponse la misma forma que usan los servicios: {code, status, errors, data}, donde data es el campo de la
// operacion pedida (objeto plano o lista, tal cual el JSON). Implementa exceptions.ApiResponseInterface.
type ApiResponse struct {
	Code   int
	Status bool
	Errors []string
	Data   any
}

func (r *ApiResponse) GetCode() int {
	return r.Code
}

func (r *ApiResponse) GetErrors() []string {
	return r.Errors
}

// GraphqlRequester POST /graphql de la API con {query, variables} y el Bearer. Normaliza la respuesta a ApiResponse.
// GraphQL responde 200 tambien con errores: el codigo real viaja en errors[0].extensions.code.
// Avisa al empezar y al terminar cada llamada (tambien si falla): la interfaz muestra el progreso y, al terminar,
// cuanto ha tardado (tiempo de respuesta medido en el navegador: red + back + lectura del JSON).
// No captura: fallo de red o una respuesta que no es JSON vuelven como error. Una sola instancia: la url se fija al arrancar.
type GraphqlRequester struct {
	dispatcher *dispatcher.Dispatcher
	jsvaluer   *jsvaluer.Jsvaluer
	promiser   *promiser.Promiser
	apiBaseUrl string
}

var instance *GraphqlRequester

func GetInstance() *GraphqlRequester {
	if instance == nil {
		instance = &GraphqlRequester{
			dispatcher: dispatcher.GetInstance(),
			jsvaluer:   jsvaluer.GetInstance(),
			promiser:   promiser.GetInstance(),
		}
	}
	return instance
}

func (g *GraphqlRequester) SetApiBaseUrl(apiBaseUrl string) {
	g.apiBaseUrl = trailingSlashesRegex.ReplaceAllString(strings.TrimSpace(apiBaseUrl), "")
}

// GetOperationResult operationName: campo raiz de la query/mutation cuyo resultado se devuelve en data.
// apiToken: "" para operaciones publicas (createUser, logIn).
func (g *GraphqlRequester) GetOperationResult(
	operationName string, query string, variables map[string]any, apiToken string,
) (*ApiResponse, error) {
	headers := map[string]any{"Accept": "application/json", "Content-Type": "application/json"}
	if apiToken != "" {
		headers["Authorization"] = "Bearer " + apiToken
	}
	if variables == nil {
		variables = map[string]any{}
	}
	body, err := json.Marshal(map[string]any{"query": query, "variables": variables})
	if err != nil {
		return nil, err
	}

	g.dispatcher.Dispatch(sharedenums.SharedEventEnum.API_REQUEST_STARTED, nil)
	performance := js.Global().Get("performance")
	startedAt := performance.Call("now").Float()
	response, responseJson, err := g.getResponseAndJson(headers, string(body))
	// isOk: llego JSON y sin errores (un fallo de red o un error de negocio cuentan como fallidas)
	_, hasErrors := responseJson["errors"]
	g.dispatcher.Dispatch(sharedenums.SharedEventEnum.API_REQUEST_FINISHED, map[string]any{
		"operationName": operationName,
		"durationMs":    performance.Call("now").Float() - startedAt,
		"isOk":          err == nil && !hasErrors,
	})
	if err != nil {
		return nil, err
	}

	jsonErrors := application.ToMaps(responseJson["errors"])
	if len(jsonErrors) == 0 {
		return &ApiResponse{
			Code:   CODE_OK,
			Status: true,
			Errors: []string{},
			Data:   application.ToMap(responseJson["data"])[operationName],
		}, nil
	}
	return &ApiResponse{
		Code:   g.getErrorCode(jsonErrors[0], response),
		Status: false,
		Errors: g.getErrors(jsonErrors),
		Data:   nil,
	}, nil
}

// getResponseAndJson el fetch y su JSON (un objeto: lo que no lo es, como un HTML de error del proxy, es un error).
func (g *GraphqlRequester) getResponseAndJson(headers map[string]any, body string) (js.Value, map[string]any, error) {
	response, err := g.promiser.Await(js.Global().Call("fetch", g.apiBaseUrl+GRAPHQL_PATH, g.jsvaluer.GetJsValue(map[string]any{
		"method":  "POST",
		"headers": headers,
		"body":    body,
	})))
	if err != nil {
		return js.Undefined(), nil, err
	}
	responseText, err := g.promiser.Await(response.Call("text"))
	if err != nil {
		return response, nil, err
	}

	var responseJson map[string]any
	if err := json.Unmarshal([]byte(responseText.String()), &responseJson); err != nil {
		return response, nil, err
	}
	if responseJson == nil {
		return response, nil, errors.New("GraphqlRequester: the response is not a JSON object")
	}
	return response, responseJson, nil
}

// getErrorCode extensions.code ?? (response.ok ? 400 : response.status)
func (g *GraphqlRequester) getErrorCode(firstError map[string]any, response js.Value) int {
	if code, ok := application.ToMap(firstError["extensions"])["code"]; ok && code != nil {
		return application.ToInt(code)
	}
	if response.Get("ok").Bool() {
		return CODE_GRAPHQL_ERROR
	}
	return response.Get("status").Int()
}

// getErrors extensions.errors del primero (detalle por campo) ?? el message de cada error.
func (g *GraphqlRequester) getErrors(jsonErrors []map[string]any) []string {
	if extensionErrors, ok := application.ToMap(jsonErrors[0]["extensions"])["errors"].([]any); ok {
		messages := make([]string, 0, len(extensionErrors))
		for _, extensionError := range extensionErrors {
			messages = append(messages, application.ToString(extensionError))
		}
		return messages
	}
	messages := make([]string, 0, len(jsonErrors))
	for _, jsonError := range jsonErrors {
		messages = append(messages, application.ToString(jsonError["message"]))
	}
	return messages
}
