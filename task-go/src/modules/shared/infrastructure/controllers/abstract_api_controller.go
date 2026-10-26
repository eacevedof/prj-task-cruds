package controllers

import (
	"bytes"
	"encoding/json"

	"task-go/src/core/config"
	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
	"task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/shared/infrastructure/traits"
	"task-go/src/modules/users/application/authenticateuser"
)

// JsonEnvelope las claves de HttpEnum (KEY_CODE, KEY_STATUS, KEY_ERRORS, KEY_DATA) en un struct: asi salen en ese
// orden, como en task-php (un map saldria ordenado alfabeticamente).
type JsonEnvelope struct {
	Code   int      `json:"code"`
	Status bool     `json:"status"`
	Errors []string `json:"errors"`
	Data   any      `json:"data"`
}

// AbstractApiController base de los controllers de la API. Envelope UNICO de respuesta:
//
//	{"code": int, "status": bool, "errors": string[], "data": object|array}
type AbstractApiController struct {
	traits.LogTrait
	traits.MailTrait
	httpRequest *httprequester.HttpRequester
}

func NewAbstractApiController(httpRequester *httprequester.HttpRequester) AbstractApiController {
	logTrait := traits.NewLogTrait(httpRequester)
	return AbstractApiController{
		LogTrait:    logTrait,
		MailTrait:   traits.NewMailTrait(logTrait),
		httpRequest: httpRequester,
	}
}

func (c *AbstractApiController) GetHttpRequest() *httprequester.HttpRequester {
	return c.httpRequest
}

// GetAuthUserOrFail se llama DENTRO del controller: el 401 (UsersException) lo mapea su rama de error como cualquier
// 4xx.
func (c *AbstractApiController) GetAuthUserOrFail() (map[string]any, error) {
	return getAuthUserOrFail(c.httpRequest)
}

func (c *AbstractApiController) RespondOk(data any) *httpresponder.HttpResponder {
	return c.getJsonResponse(enums.ExceptionEnum.CODE_OK, true, []string{}, data)
}

func (c *AbstractApiController) RespondErrors(code int, errors []string) *httpresponder.HttpResponder {
	return c.getJsonResponse(code, false, errors, []any{})
}

// RespondDomainError excepcion controlada (de dominio o FieldsException): su codigo 4xx y su mensaje viajan al
// cliente tal cual (una FieldsException ya trae en el mensaje el detalle por campo). El controller la distingue por
// su tipo.
func (c *AbstractApiController) RespondDomainError(ex exceptions.InterfaceException) *httpresponder.HttpResponder {
	return c.RespondErrors(ex.GetCode(), []string{ex.Error()})
}

// RespondUnexpectedError cualquier otra cosa: 500, se loguea con el metodo que fallo y no filtra su mensaje.
func (c *AbstractApiController) RespondUnexpectedError(err error, logTitle string) *httpresponder.HttpResponder {
	c.LogException(err, logTitle)
	c.SendMailOnThrowable(err, logTitle)
	return c.RespondErrors(
		enums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
		[]string{config.Tr(enums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)},
	)
}

// GetJsonEncoded json_encode con JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES: sin escapar <, > ni &.
func GetJsonEncoded(value any) string {
	var jsonBuffer bytes.Buffer
	jsonEncoder := json.NewEncoder(&jsonBuffer)
	jsonEncoder.SetEscapeHTML(false)
	if err := jsonEncoder.Encode(value); err != nil {
		return ""
	}
	return string(bytes.TrimRight(jsonBuffer.Bytes(), "\n"))
}

func (c *AbstractApiController) getJsonResponse(code int, status bool, errors []string, data any) *httpresponder.HttpResponder {
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

// getAuthUserOrFail el token Bearer de la peticion -> authUserId + authUserUuid (o el 401 como UsersException).
func getAuthUserOrFail(httpRequester *httprequester.HttpRequester) (map[string]any, error) {
	authenticateUserResultDto, err := authenticateuser.AuthenticateUserServiceGetInstance().Invoke(
		authenticateuser.AuthenticateUserDtoFromPrimitives(application.MergePrimitives(
			map[string]any{"apiToken": httpRequester.GetBearerToken()},
			httpRequester.GetMetadata(),
		)),
	)
	if err != nil {
		return nil, err
	}
	return authenticateUserResultDto.ToAuthPrimitives(), nil
}
