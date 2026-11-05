package controllers

import (
	"fmt"
	"strings"

	"task-go/src/core/config"
	"task-go/src/modules/graphql/application/executegraphqlquery"
	"task-go/src/modules/graphql/domain/enums"
	"task-go/src/modules/graphql/infrastructure/providers"
	sharedenums "task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
)

// ExecuteGraphQlQueryController POST /graphql  body: {query, variables?, operationName?}
// header: Authorization: Bearer <api_token> (cada resolver autentica; sin token solo responden createUser y logIn).
// Solo traduce HTTP <-> dto: el schema y los resolvers los registra cada modulo (un XxxGraphQlSchemaProvider por
// modulo, en el orden de src/core/routes/graphql_schema.go) y ExecuteGraphQlQueryService los ejecuta.
// La respuesta es la de GraphQL ({data, errors}), no el envelope REST: un error de dominio va en errors[].extensions
// (ver GraphQlException) con status 200, como manda GraphQL sobre HTTP.
type ExecuteGraphQlQueryController struct {
	sharedcontrollers.AbstractHttpController
	executeGraphQlQueryService *executegraphqlquery.ExecuteGraphQlQueryService
}

// ExecuteGraphQlQueryControllerGetInstance graphqlSchemaRegistry lo monta core con la lista de providers (ver
// GraphQlSchemaProvidersRegistry: el modulo graphql no puede importar los providers de users y tasks).
func ExecuteGraphQlQueryControllerGetInstance(
	httpRequester *httprequester.HttpRequester,
	graphqlSchemaRegistry *providers.GraphQlSchemaProvidersRegistry,
) *ExecuteGraphQlQueryController {
	return &ExecuteGraphQlQueryController{
		AbstractHttpController:     sharedcontrollers.NewAbstractHttpController(httpRequester),
		executeGraphQlQueryService: executegraphqlquery.ExecuteGraphQlQueryServiceGetInstance(graphqlSchemaRegistry),
	}
}

func (c *ExecuteGraphQlQueryController) Invoke() (httpResponder *httpresponder.HttpResponder) {
	executeGraphQlRequestDto := executegraphqlquery.ExecuteGraphQlRequestDtoFromPrimitives(c.GetHttpRequest().GetPost())

	if executeGraphQlRequestDto.GetQuery() == "" {
		return c.getGraphQlResponse(
			sharedenums.ExceptionEnum.CODE_BAD_REQUEST,
			executegraphqlquery.ExecuteGraphQlResultDtoFromErrorMessage(
				config.Tr(enums.GraphQlMessageEnum.GRAPHQL_QUERY_IS_REQUIRED),
			),
		)
	}

	// el catch Throwable de task-php: un error del servicio o un panic
	defer func() {
		if recovered := recover(); recovered != nil {
			httpResponder = c.getUnexpectedErrorResponse(fmt.Errorf("%v", recovered))
		}
	}()

	executeGraphQlResultDto, err := c.executeGraphQlQueryService.Invoke(c.GetHttpRequest(), executeGraphQlRequestDto)
	if err != nil {
		return c.getUnexpectedErrorResponse(err)
	}
	return c.getGraphQlResponse(sharedenums.ExceptionEnum.CODE_OK, executeGraphQlResultDto)
}

func (c *ExecuteGraphQlQueryController) getUnexpectedErrorResponse(err error) *httpresponder.HttpResponder {
	c.LogException(err, "ExecuteGraphQlQueryController.Invoke")
	return c.getGraphQlResponse(
		sharedenums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
		executegraphqlquery.ExecuteGraphQlResultDtoFromErrorMessage(
			config.Tr(sharedenums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED),
		),
	)
}

func (c *ExecuteGraphQlQueryController) getGraphQlResponse(
	httpCode int,
	graphqlResultDto *executegraphqlquery.ExecuteGraphQlResultDto,
) *httpresponder.HttpResponder {
	return httpresponder.Json(
		getHtmlUnescapedJson(sharedcontrollers.GetJsonEncoded(graphqlResultDto.ToArray())),
		httpCode,
		map[string]string{},
	)
}

// getHtmlUnescapedJson graph-gophers escapa <, > y & dentro de data (barra + u003c...); task-php los deja tal cual
// (JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES). Solo se tocan esos tres escapes: un "\\" se copia entero para no
// confundir una barra escapada con el inicio de un \u.
func getHtmlUnescapedJson(json string) string {
	// las claves son las secuencias de 6 caracteres barra-u-003c...: se escriben con "\\" + "u003c"
	htmlEscapes := map[string]byte{"\\" + "u003c": '<', "\\" + "u003e": '>', "\\" + "u0026": '&'}
	var unescaped strings.Builder
	for i := 0; i < len(json); i++ {
		if json[i] != '\\' || i+1 >= len(json) {
			unescaped.WriteByte(json[i])
			continue
		}
		if i+6 <= len(json) {
			if htmlChar, isHtmlEscape := htmlEscapes[json[i:i+6]]; isHtmlEscape {
				unescaped.WriteByte(htmlChar)
				i += 5
				continue
			}
		}
		unescaped.WriteString(json[i : i+2])
		i++
	}
	return unescaped.String()
}
