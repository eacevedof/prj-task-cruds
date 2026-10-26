package controllers

import (
	"task-go/src/core/config"
	"task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/shared/infrastructure/traits"
)

// AbstractGraphqlController base de los resolvers GraphQL de cada modulo: son controllers (construyen el DTO, llaman
// al servicio y distinguen el error), pero en vez de un HttpResponder devuelven el ResultDto o un GraphqlError.
type AbstractGraphqlController struct {
	traits.LogTrait
	traits.MailTrait
	httpRequest *httprequester.HttpRequester
}

func NewAbstractGraphqlController(httpRequester *httprequester.HttpRequester) AbstractGraphqlController {
	logTrait := traits.NewLogTrait(httpRequester)
	return AbstractGraphqlController{
		LogTrait:    logTrait,
		MailTrait:   traits.NewMailTrait(logTrait),
		httpRequest: httpRequester,
	}
}

func (c *AbstractGraphqlController) GetHttpRequest() *httprequester.HttpRequester {
	return c.httpRequest
}

// GetAuthUserOrFail se llama DENTRO del resolver: el 401 (UsersException) lo mapea su rama de error como
// cualquier 4xx.
func (c *AbstractGraphqlController) GetAuthUserOrFail() (map[string]any, error) {
	return getAuthUserOrFail(c.httpRequest)
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
	unexpectedError := config.Tr(enums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)
	return &GraphqlError{
		message: unexpectedError,
		extensions: map[string]any{
			enums.GraphqlEnum.EXTENSION_CODE:   enums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
			enums.GraphqlEnum.EXTENSION_ERRORS: []string{unexpectedError},
		},
	}
}
