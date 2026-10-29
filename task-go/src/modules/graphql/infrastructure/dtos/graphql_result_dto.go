package dtos

import (
	"encoding/json"

	"task-go/src/modules/graphql/domain/enums"
)

// GraphqlResultPrimitives respuesta GraphQL {errors?, data?}: las claves solo van si traen algo y en el orden de
// webonyx (errors primero). data va en crudo para no perder el orden de los campos de la consulta.
type GraphqlResultPrimitives struct {
	Errors []any           `json:"errors,omitempty"`
	Data   json.RawMessage `json:"data,omitempty"`
}

type GraphqlResultDto struct {
	errors []any
	data   json.RawMessage
}

// GraphqlResultDtoFromPrimitives errors y data del resultado de graph-gophers. Un data "null" cuenta como ausente.
func GraphqlResultDtoFromPrimitives(errors []any, data json.RawMessage) *GraphqlResultDto {
	if string(data) == "null" {
		data = nil
	}
	return &GraphqlResultDto{errors: errors, data: data}
}

// GraphqlResultDtoFromErrorMessage error antes de ejecutar (peticion sin query, fallo inesperado): sin data, como lo
// daria GraphQL.
func GraphqlResultDtoFromErrorMessage(message string) *GraphqlResultDto {
	return &GraphqlResultDto{errors: []any{map[string]any{enums.GraphqlEnum.KEY_MESSAGE: message}}}
}

func (d *GraphqlResultDto) ToArray() GraphqlResultPrimitives {
	return GraphqlResultPrimitives{Errors: d.errors, Data: d.data}
}
