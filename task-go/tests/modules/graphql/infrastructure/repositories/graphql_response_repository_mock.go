package repositories

import (
	"encoding/json"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/graphql/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
)

// GraphqlResponseRepositoryMock lectura de una respuesta GraphQL:
// {"data": {...}, "errors": [{"message", "extensions": {"code", "errors"}}]}
type GraphqlResponseRepositoryMock struct {
	responseArray map[string]any
}

func GraphqlResponseRepositoryMockFromHttpResponse(httpResponse *httpresponder.HttpResponder) *GraphqlResponseRepositoryMock {
	responseArray := map[string]any{}
	_ = json.Unmarshal([]byte(httpResponse.GetBody()), &responseArray)
	return &GraphqlResponseRepositoryMock{responseArray: responseArray}
}

func (m *GraphqlResponseRepositoryMock) GetData() map[string]any {
	data, _ := m.responseArray["data"].(map[string]any)
	return data
}

func (m *GraphqlResponseRepositoryMock) GetErrors() []any {
	errors, _ := m.responseArray[enums.GraphqlEnum.KEY_ERRORS].([]any)
	return errors
}

// GetFirstErrorCode codigo HTTP equivalente REST del primer error (0 si es un error de GraphQL, sin extensions).
func (m *GraphqlResponseRepositoryMock) GetFirstErrorCode() int {
	return application.ToInt(m.getFirstErrorExtensions()[enums.GraphqlEnum.EXTENSION_CODE])
}

func (m *GraphqlResponseRepositoryMock) GetFirstErrorDetails() []string {
	details, _ := m.getFirstErrorExtensions()[enums.GraphqlEnum.EXTENSION_ERRORS].([]any)
	var errorDetails []string
	for _, detail := range details {
		errorDetails = append(errorDetails, application.ToString(detail))
	}
	return errorDetails
}

// GetColumn el array_column de PHP: el valor de key en cada elemento de una lista de objetos.
func GetColumn(list any, key string) []any {
	items, _ := list.([]any)
	column := []any{}
	for _, item := range items {
		if object, isObject := item.(map[string]any); isObject {
			column = append(column, object[key])
		}
	}
	return column
}

func (m *GraphqlResponseRepositoryMock) getFirstErrorExtensions() map[string]any {
	errors := m.GetErrors()
	if len(errors) == 0 {
		return map[string]any{}
	}
	firstError, _ := errors[0].(map[string]any)
	extensions, _ := firstError["extensions"].(map[string]any)
	return extensions
}
