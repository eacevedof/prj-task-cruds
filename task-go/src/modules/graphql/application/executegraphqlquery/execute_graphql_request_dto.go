package executegraphqlquery

import (
	"encoding/json"
	"strings"

	"task-go/src/modules/graphql/domain/enums"
	"task-go/src/modules/shared/application"
)

// ExecuteGraphQlRequestDto body de POST /graphql: {query, variables?, operationName?}.
type ExecuteGraphQlRequestDto struct {
	query         string
	variables     map[string]any
	operationName string
}

func ExecuteGraphQlRequestDtoFromPrimitives(primitives map[string]any) *ExecuteGraphQlRequestDto {
	return &ExecuteGraphQlRequestDto{
		query:         getScalarAsString(primitives[enums.GraphQlEnum.KEY_QUERY]),
		variables:     getVariablesAsMap(primitives[enums.GraphQlEnum.KEY_VARIABLES]),
		operationName: getScalarAsString(primitives[enums.GraphQlEnum.KEY_OPERATION_NAME]),
	}
}

// getScalarAsString un array/objeto donde va un string cuenta como vacio (como HttpRequester.GetPostString), no
// como "map[...]".
func getScalarAsString(value any) string {
	switch value.(type) {
	case map[string]any, []any:
		return ""
	}
	return strings.TrimSpace(application.ToString(value))
}

// getVariablesAsMap "variables" puede llegar como objeto JSON o, en algunos clientes, como string JSON.
func getVariablesAsMap(variables any) map[string]any {
	if jsonVariables, isString := variables.(string); isString {
		var decodedVariables map[string]any
		if err := json.Unmarshal([]byte(jsonVariables), &decodedVariables); err != nil {
			return map[string]any{}
		}
		variables = decodedVariables
	}
	if mapVariables, isMap := variables.(map[string]any); isMap && mapVariables != nil {
		return mapVariables
	}
	return map[string]any{}
}

func (d *ExecuteGraphQlRequestDto) GetQuery() string {
	return d.query
}

func (d *ExecuteGraphQlRequestDto) GetVariables() map[string]any {
	return d.variables
}

// GetOperationName "" = sin operationName (el null de task-php).
func (d *ExecuteGraphQlRequestDto) GetOperationName() string {
	return d.operationName
}
