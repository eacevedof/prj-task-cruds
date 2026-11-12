package routes

import (
	"task-go/src/core/boot"
	graphqlcontrollers "task-go/src/modules/graphql/infrastructure/controllers"
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
// resolver autentica con GetAuthUserOrFail() (AbstractHttpController).
func GetRoutes() []boot.Route {
	return []boot.Route{
		{Url: "/health", Allowed: []string{"get"}, Controller: func(h *httprequester.HttpRequester, args map[string]string) *httpresponder.HttpResponder {
			return sharedcontrollers.HealthControllerGetInstance(h).Invoke()
		}},

		//@documentation (publica; fuente: los .md de src/modules/open/documentation/infrastructure/views)
		{Url: "/api/v1", Allowed: []string{"get"}, Controller: func(h *httprequester.HttpRequester, args map[string]string) *httpresponder.HttpResponder {
			return documentationcontrollers.DocumentationControllerGetInstance(h).Invoke()
		}},
		{Url: "/api/v1/changelog", Allowed: []string{"get"}, Controller: func(h *httprequester.HttpRequester, args map[string]string) *httpresponder.HttpResponder {
			return documentationcontrollers.ChangelogControllerGetInstance(h).Invoke()
		}},

		//@auth
		{Url: "/api/v1/log-in", Allowed: []string{"post"}, Controller: func(h *httprequester.HttpRequester, args map[string]string) *httpresponder.HttpResponder {
			return userscontrollers.LogInUserRestControllerGetInstance(h).Invoke()
		}},

		//@graphql ExecuteGraphQlQueryController (cada modulo registra su XxxGraphQlSchemaProvider en graphql_schema_providers.go;
		// schema: src/modules/graphql/infrastructure/providers/schema.graphql + infrastructure/providers/<modulo>.graphql)
		{Url: "/graphql", Allowed: []string{"post"}, Controller: func(h *httprequester.HttpRequester, args map[string]string) *httpresponder.HttpResponder {
			return graphqlcontrollers.ExecuteGraphQlQueryControllerGetInstance(h).Invoke()
		}},
	}
}
