package executegraphqlquery

import (
	"encoding/json"

	"task-go/src/modules/graphql/domain/enums"
)

// ExecuteGraphQlResultPrimitives respuesta GraphQL {errors?, data?}: las claves solo van si traen algo y en el orden de
// webonyx (errors primero). data va en crudo para no perder el orden de los campos de la consulta.
type ExecuteGraphQlResultPrimitives struct {
	Errors []any           `json:"errors,omitempty"`
	Data   json.RawMessage `json:"data,omitempty"`
}

type ExecuteGraphQlResultDto struct {
	errors []any
	data   json.RawMessage
}

// ExecuteGraphQlResultDtoFromPrimitives errors y data del resultado de graph-gophers. Un data "null" cuenta como ausente.
func ExecuteGraphQlResultDtoFromPrimitives(errors []any, data json.RawMessage) *ExecuteGraphQlResultDto {
	if string(data) == "null" {
		data = nil
	}
	return &ExecuteGraphQlResultDto{errors: errors, data: data}
}

// ExecuteGraphQlResultDtoFromErrorMessage error antes de ejecutar (peticion sin query, fallo inesperado): sin data, como lo
// daria GraphQL.
func ExecuteGraphQlResultDtoFromErrorMessage(message string) *ExecuteGraphQlResultDto {
	return &ExecuteGraphQlResultDto{errors: []any{map[string]any{enums.GraphQlEnum.KEY_MESSAGE: message}}}
}

func (d *ExecuteGraphQlResultDto) ToArray() ExecuteGraphQlResultPrimitives {
	return ExecuteGraphQlResultPrimitives{Errors: d.errors, Data: d.data}
}
