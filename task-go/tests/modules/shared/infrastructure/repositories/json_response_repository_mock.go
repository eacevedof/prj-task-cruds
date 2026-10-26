package repositories

import (
	"encoding/json"
	"fmt"
	"os"
	"strings"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
)

// JsonResponseRepositoryMock lectura del envelope de la API: {"code": int, "status": bool, "errors": string[], "data": array}
type JsonResponseRepositoryMock struct {
	responseArray map[string]any
}

func JsonResponseRepositoryMockFromPrimitives(responseContent string) *JsonResponseRepositoryMock {
	responseArray := map[string]any{}
	_ = json.Unmarshal([]byte(responseContent), &responseArray)
	return &JsonResponseRepositoryMock{responseArray: responseArray}
}

func JsonResponseRepositoryMockFromHttpResponse(httpResponse *httpresponder.HttpResponder) *JsonResponseRepositoryMock {
	return JsonResponseRepositoryMockFromPrimitives(httpResponse.GetBody())
}

func (m *JsonResponseRepositoryMock) GetCode() int {
	return application.ToInt(m.responseArray["code"])
}

func (m *JsonResponseRepositoryMock) GetStatus() bool {
	status, _ := m.responseArray["status"].(bool)
	return status
}

func (m *JsonResponseRepositoryMock) GetErrors() []string {
	errors, _ := m.responseArray["errors"].([]any)
	errorMessages := []string{}
	for _, errorMessage := range errors {
		errorMessages = append(errorMessages, application.ToString(errorMessage))
	}
	return errorMessages
}

func (m *JsonResponseRepositoryMock) GetData() map[string]any {
	data, _ := m.responseArray["data"].(map[string]any)
	return data
}

func (m *JsonResponseRepositoryMock) GetDebug(useCase string) {
	debug := ""
	if useCase != "" {
		debug = "useCase: " + useCase + ", "
	}
	debug += fmt.Sprintf("code: %d, status: %t, errors: %s", m.GetCode(), m.GetStatus(), strings.Join(m.GetErrors(), " | "))
	fmt.Fprintln(os.Stderr, debug)
}
