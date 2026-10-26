package graphql

import (
	"context"
	_ "embed"
	"encoding/json"
	"fmt"
	"strings"
	"sync"

	graphqlgo "github.com/graph-gophers/graphql-go"

	"task-go/src/core/config"
	"task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
	"task-go/src/modules/shared/infrastructure/controllers"
	taskscontrollers "task-go/src/modules/tasks/infrastructure/controllers"
	userscontrollers "task-go/src/modules/users/infrastructure/controllers"
)

//go:embed schema.graphql
var schemaGraphql string

// el schema se parsea y se valida contra los resolvers una vez por proceso; los controllers de cada peticion le
// llegan por el context (resolversByType)
var (
	schema     *graphqlgo.Schema
	schemaOnce sync.Once
)

// GraphqlController POST /graphql  body: {query, variables?, operationName?}  header: Authorization: Bearer <api_token>
// (cada resolver autentica; sin token solo responden createUser y logIn).
// Vive en core y no en shared porque cablea los resolvers de varios modulos (shared no depende de ellos).
// La respuesta es la de GraphQL ({data, errors}), no el envelope REST: un error de dominio va en
// errors[].extensions (ver AbstractGraphqlController) con status 200, como manda GraphQL sobre HTTP.
type GraphqlController struct {
	controllers.AbstractApiController
	usersGraphqlController *userscontrollers.UsersGraphqlController
	tasksGraphqlController *taskscontrollers.TasksGraphqlController
}

func GraphqlControllerGetInstance(httpRequester *httprequester.HttpRequester) *GraphqlController {
	return &GraphqlController{
		AbstractApiController:  controllers.NewAbstractApiController(httpRequester),
		usersGraphqlController: userscontrollers.UsersGraphqlControllerGetInstance(httpRequester),
		tasksGraphqlController: taskscontrollers.TasksGraphqlControllerGetInstance(httpRequester),
	}
}

func (c *GraphqlController) Execute() (httpResponder *httpresponder.HttpResponder) {
	query := c.GetHttpRequest().GetPostString(enums.GraphqlEnum.KEY_QUERY)
	if query == "" {
		return c.getGraphqlErrorResponse(
			enums.ExceptionEnum.CODE_BAD_REQUEST,
			config.Tr(enums.SharedMessageEnum.GRAPHQL_QUERY_IS_REQUIRED),
		)
	}

	defer func() {
		if recovered := recover(); recovered != nil {
			c.LogException(fmt.Errorf("%v", recovered), "GraphqlController.Execute")
			httpResponder = c.getGraphqlErrorResponse(
				enums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
				config.Tr(enums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED),
			)
		}
	}()

	executionResult := getSchema().Exec(
		context.WithValue(context.Background(), resolversByTypeKey{}, &resolversByType{
			usersGraphqlController: c.usersGraphqlController,
			tasksGraphqlController: c.tasksGraphqlController,
		}),
		query,
		c.GetHttpRequest().GetPostString(enums.GraphqlEnum.KEY_OPERATION_NAME),
		c.getVariables(),
	)
	return c.getGraphqlResponse(enums.ExceptionEnum.CODE_OK, executionResult)
}

func getSchema() *graphqlgo.Schema {
	schemaOnce.Do(func() {
		schema = graphqlgo.MustParseSchema(
			schemaGraphql,
			&rootResolver{},
			graphqlgo.MaxDepth(enums.GraphqlEnum.MAX_QUERY_DEPTH),
		)
	})
	return schema
}

// getVariables "variables" puede llegar como objeto JSON o, en algunos clientes, como string JSON.
func (c *GraphqlController) getVariables() map[string]any {
	variables := c.GetHttpRequest().GetPostValue(enums.GraphqlEnum.KEY_VARIABLES, map[string]any{})
	if jsonVariables, isString := variables.(string); isString {
		var decodedVariables map[string]any
		if err := json.Unmarshal([]byte(jsonVariables), &decodedVariables); err != nil {
			return map[string]any{}
		}
		return decodedVariables
	}
	if mapVariables, isMap := variables.(map[string]any); isMap {
		return mapVariables
	}
	return map[string]any{}
}

func (c *GraphqlController) getGraphqlErrorResponse(code int, message string) *httpresponder.HttpResponder {
	return c.getGraphqlResponse(code, map[string]any{
		enums.GraphqlEnum.KEY_ERRORS: []map[string]any{{enums.GraphqlEnum.KEY_MESSAGE: message}},
	})
}

func (c *GraphqlController) getGraphqlResponse(code int, graphqlResult any) *httpresponder.HttpResponder {
	return httpresponder.Json(getHtmlUnescapedJson(controllers.GetJsonEncoded(graphqlResult)), code, map[string]string{})
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
