package controllers

import (
	"task-go/src/core/config"
	"task-go/src/modules/graphql/domain/enums"
	sharedenums "task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	"task-go/src/modules/shared/infrastructure/exceptions"
)

// AbstractGraphqlController base de los resolvers GraphQL de cada modulo (XxxGraphqlController): son controllers
// (construyen el DTO, llaman al servicio y distinguen el error), pero en vez de un HttpResponder devuelven el
// ResultDto o un GraphqlError. Extiende la AbstractHttpController de shared.
type AbstractGraphqlController struct {
	sharedcontrollers.AbstractHttpController
}

func NewAbstractGraphqlController(httpRequester *httprequester.HttpRequester) AbstractGraphqlController {
	return AbstractGraphqlController{AbstractHttpController: sharedcontrollers.NewAbstractHttpController(httpRequester)}
}

// GetGraphqlDomainError excepcion controlada (de dominio o FieldsException), distinguida por su tipo: su codigo 4xx
// va en extensions.code y su mensaje en extensions.errors (una FieldsException ya trae en el mensaje el detalle por
// campo).
func (c *AbstractGraphqlController) GetGraphqlDomainError(ex exceptions.InterfaceException) *GraphqlError {
	return &GraphqlError{
		message: ex.Error(),
		extensions: map[string]any{
			enums.GraphqlEnum.EXTENSION_CODE:   ex.GetCode(),
			enums.GraphqlEnum.EXTENSION_ERRORS: []string{ex.Error()},
		},
	}
}

// GetGraphqlUnexpectedError cualquier otra cosa: 500, se loguea con el resolver que fallo y no filtra su mensaje.
func (c *AbstractGraphqlController) GetGraphqlUnexpectedError(err error, logTitle string) *GraphqlError {
	c.LogException(err, logTitle)
	c.SendMailOnThrowable(err, logTitle)
	unexpectedError := config.Tr(sharedenums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)
	return &GraphqlError{
		message: unexpectedError,
		extensions: map[string]any{
			enums.GraphqlEnum.EXTENSION_CODE:   sharedenums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
			enums.GraphqlEnum.EXTENSION_ERRORS: []string{unexpectedError},
		},
	}
}
