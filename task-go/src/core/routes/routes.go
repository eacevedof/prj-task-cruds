package routes

import (
	"task-go/src/core/boot"
	"task-go/src/modules/graphql/infrastructure/controllers/graphqlcontroller"
	documentationcontrollers "task-go/src/modules/open/documentation/infrastructure/controllers"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	userscontrollers "task-go/src/modules/users/infrastructure/controllers"
)

// GetRoutes rutas de la API (ApiRouter). Url con tags ":nombre" -> args con ese nombre para el controller.
// Allowed: metodos HTTP en minusculas (HEAD va implicito en GET).
// Solo login (REST) y GraphQL: el resto de operaciones van por /graphql.
// Auth: /graphql exige "Authorization: Bearer <api_token>" salvo createUser y logIn. No lo decide el router: cada
// resolver autentica con GetAuthUserOrFail() (AbstractGraphqlController).
func GetRoutes() []boot.Route {
	return []boot.Route{
		{Url: "/health", Allowed: []string{"get"}, Controller: func(h *httprequester.HttpRequester, args map[string]string) *httpresponder.HttpResponder {
			return sharedcontrollers.HealthControllerGetInstance(h).Index()
		}},

		//@documentation (publica; fuente: los .md de src/modules/open/documentation/infrastructure/views)
		{Url: "/api/v1", Allowed: []string{"get"}, Controller: func(h *httprequester.HttpRequester, args map[string]string) *httpresponder.HttpResponder {
			return documentationcontrollers.DocumentationControllerGetInstance(h).Show()
		}},
		{Url: "/api/v1/changelog", Allowed: []string{"get"}, Controller: func(h *httprequester.HttpRequester, args map[string]string) *httpresponder.HttpResponder {
			return documentationcontrollers.ChangelogControllerGetInstance(h).Show()
		}},

		//@auth
		{Url: "/api/v1/log-in", Allowed: []string{"post"}, Controller: func(h *httprequester.HttpRequester, args map[string]string) *httpresponder.HttpResponder {
			return userscontrollers.LogInUserRestControllerGetInstance(h).LogIn()
		}},

		//@graphql (schema: src/modules/graphql/infrastructure/schema/schema.graphql + infrastructure/providers/<modulo>.graphql
		// de cada modulo, en el orden de graphql_schema.go)
		{Url: "/graphql", Allowed: []string{"post"}, Controller: func(h *httprequester.HttpRequester, args map[string]string) *httpresponder.HttpResponder {
			return graphqlcontroller.GraphqlControllerGetInstance(h, GetGraphqlSchemaRegistry()).Execute()
		}},
	}
}
