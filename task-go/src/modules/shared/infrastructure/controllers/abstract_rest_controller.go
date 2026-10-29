package controllers

import (
	"task-go/src/core/config"
	"task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
	"task-go/src/modules/shared/infrastructure/exceptions"
)

// JsonEnvelope las claves de HttpEnum (KEY_CODE, KEY_STATUS, KEY_ERRORS, KEY_DATA) en un struct: asi salen en ese
// orden, como en task-php (un map saldria ordenado alfabeticamente).
type JsonEnvelope struct {
	Code   int      `json:"code"`
	Status bool     `json:"status"`
	Errors []string `json:"errors"`
	Data   any      `json:"data"`
}

// AbstractRestController base de los controllers REST. Envelope UNICO de respuesta, con el codigo tambien como
// status HTTP:
//
//	{"code": int, "status": bool, "errors": string[], "data": object|array}
type AbstractRestController struct {
	AbstractHttpController
}

func NewAbstractRestController(httpRequester *httprequester.HttpRequester) AbstractRestController {
	return AbstractRestController{AbstractHttpController: NewAbstractHttpController(httpRequester)}
}

func (c *AbstractRestController) RespondOk(data any) *httpresponder.HttpResponder {
	return c.getJsonResponse(enums.ExceptionEnum.CODE_OK, true, []string{}, data)
}

func (c *AbstractRestController) RespondErrors(code int, errors []string) *httpresponder.HttpResponder {
	return c.getJsonResponse(code, false, errors, []any{})
}

// RespondDomainError excepcion controlada (de dominio o FieldsException): su codigo 4xx y su mensaje viajan al
// cliente tal cual (una FieldsException ya trae en el mensaje el detalle por campo). El controller la distingue por
// su tipo.
func (c *AbstractRestController) RespondDomainError(ex exceptions.InterfaceException) *httpresponder.HttpResponder {
	return c.RespondErrors(ex.GetCode(), []string{ex.Error()})
}

// RespondUnexpectedError cualquier otra cosa: 500, se loguea con el metodo que fallo y no filtra su mensaje.
func (c *AbstractRestController) RespondUnexpectedError(err error, logTitle string) *httpresponder.HttpResponder {
	c.LogException(err, logTitle)
	c.SendMailOnThrowable(err, logTitle)
	return c.RespondErrors(
		enums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
		[]string{config.Tr(enums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)},
	)
}

func (c *AbstractRestController) getJsonResponse(code int, status bool, errors []string, data any) *httpresponder.HttpResponder {
	headers := map[string]string{}
	// RFC 6750: un 401 indica al cliente el esquema que se espera
	if code == enums.ExceptionEnum.CODE_UNAUTHORIZED {
		headers[enums.HttpEnum.HEADER_WWW_AUTHENTICATE] = enums.HttpEnum.AUTH_SCHEME_BEARER
	}
	return httpresponder.Json(
		GetJsonEncoded(JsonEnvelope{Code: code, Status: status, Errors: errors, Data: data}),
		code,
		headers,
	)
}
