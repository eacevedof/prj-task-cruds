package graphqlcontroller

import (
	"fmt"
	"strings"

	"task-go/src/core/config"
	"task-go/src/modules/graphql/domain/enums"
	"task-go/src/modules/graphql/infrastructure/components/graphqlexecutor"
	"task-go/src/modules/graphql/infrastructure/dtos"
	"task-go/src/modules/graphql/infrastructure/schema"
	sharedenums "task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
	"task-go/src/modules/shared/infrastructure/controllers"
)

// GraphqlController POST /graphql  body: {query, variables?, operationName?}  header: Authorization: Bearer <api_token>
// (cada resolver autentica; sin token solo responden createUser y logIn).
// Solo traduce HTTP <-> dto: el schema y los resolvers los registra cada modulo (src/core/routes/graphql_schema.go,
// un XxxGraphqlSchemaProvider por modulo) y GraphqlExecutor los ejecuta. Este modulo no importa nada de users ni tasks.
// Apagar GraphQL = quitar su ruta, src/modules/graphql, graphql_schema.go, de cada modulo su XxxGraphqlSchemaProvider
// y su <modulo>.graphql (infrastructure/providers) y sus XxxGraphqlController, y graph-gophers/graphql-go.
// La respuesta es la de GraphQL ({data, errors}), no el envelope REST: un error de dominio va en
// errors[].extensions (ver AbstractGraphqlController) con status 200, como manda GraphQL sobre HTTP.
type GraphqlController struct {
	controllers.AbstractHttpController
	graphqlExecutor *graphqlexecutor.GraphqlExecutor
}

// GraphqlControllerGetInstance graphqlSchemaRegistry lo monta core con la lista de proveedores (este paquete no puede
// importar core: ciclo de imports).
func GraphqlControllerGetInstance(
	httpRequester *httprequester.HttpRequester,
	graphqlSchemaRegistry *schema.GraphqlSchemaRegistry,
) *GraphqlController {
	return &GraphqlController{
		AbstractHttpController: controllers.NewAbstractHttpController(httpRequester),
		graphqlExecutor:        graphqlexecutor.GraphqlExecutorGetInstance(graphqlSchemaRegistry),
	}
}

func (c *GraphqlController) Execute() (httpResponder *httpresponder.HttpResponder) {
	graphqlRequestDto := dtos.GraphqlRequestDtoFromPrimitives(c.GetHttpRequest().GetPost())
	if graphqlRequestDto.GetQuery() == "" {
		return c.getGraphqlResponse(
			sharedenums.ExceptionEnum.CODE_BAD_REQUEST,
			dtos.GraphqlResultDtoFromErrorMessage(config.Tr(enums.GraphqlMessageEnum.GRAPHQL_QUERY_IS_REQUIRED)),
		)
	}

	defer func() {
		if recovered := recover(); recovered != nil {
			httpResponder = c.getUnexpectedErrorResponse(fmt.Errorf("%v", recovered))
		}
	}()

	graphqlResultDto, err := c.graphqlExecutor.Execute(c.GetHttpRequest(), graphqlRequestDto)
	if err != nil {
		return c.getUnexpectedErrorResponse(err)
	}
	return c.getGraphqlResponse(sharedenums.ExceptionEnum.CODE_OK, graphqlResultDto)
}

func (c *GraphqlController) getUnexpectedErrorResponse(err error) *httpresponder.HttpResponder {
	c.LogException(err, "GraphqlController.Execute")
	return c.getGraphqlResponse(
		sharedenums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
		dtos.GraphqlResultDtoFromErrorMessage(config.Tr(sharedenums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)),
	)
}

func (c *GraphqlController) getGraphqlResponse(code int, graphqlResultDto *dtos.GraphqlResultDto) *httpresponder.HttpResponder {
	return httpresponder.Json(
		getHtmlUnescapedJson(controllers.GetJsonEncoded(graphqlResultDto.ToArray())),
		code,
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
