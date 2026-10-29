package controllers

import (
	"bytes"
	"encoding/json"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/shared/infrastructure/traits"
	"task-go/src/modules/users/application/authenticateuser"
)

// AbstractHttpController base de todo lo que entra por HTTP: la peticion (HttpRequester), log + mail y la
// autenticacion por Bearer. Cada protocolo pone encima su forma de responder y de mapear errores:
// AbstractRestController (envelope JSON con status HTTP) y AbstractGraphqlController (ResultDto o GraphqlError). El
// endpoint /graphql (core) la usa tal cual.
type AbstractHttpController struct {
	traits.LogTrait
	traits.MailTrait
	httpRequest *httprequester.HttpRequester
}

func NewAbstractHttpController(httpRequester *httprequester.HttpRequester) AbstractHttpController {
	logTrait := traits.NewLogTrait(httpRequester)
	return AbstractHttpController{
		LogTrait:    logTrait,
		MailTrait:   traits.NewMailTrait(logTrait),
		httpRequest: httpRequester,
	}
}

func (c *AbstractHttpController) GetHttpRequest() *httprequester.HttpRequester {
	return c.httpRequest
}

// GetAuthUserOrFail se llama DENTRO del controller: el 401 (UsersException) lo mapea su rama de error como cualquier
// 4xx. El token Bearer de la peticion -> authUserId + authUserUuid.
func (c *AbstractHttpController) GetAuthUserOrFail() (map[string]any, error) {
	authenticateUserResultDto, err := authenticateuser.AuthenticateUserServiceGetInstance().Invoke(
		authenticateuser.AuthenticateUserDtoFromPrimitives(application.MergePrimitives(
			map[string]any{"apiToken": c.httpRequest.GetBearerToken()},
			c.httpRequest.GetMetadata(),
		)),
	)
	if err != nil {
		return nil, err
	}
	return authenticateUserResultDto.ToAuthPrimitives(), nil
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
