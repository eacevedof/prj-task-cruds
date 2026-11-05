package providers

import (
	"task-go/src/modules/graphql/domain/enums"
	graphqlproviders "task-go/src/modules/graphql/infrastructure/providers"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/users/infrastructure/controllers"
)

// TYPE_USER tipo que define users.graphql y extienden otros modulos (Tasks le anade tasks). Es el
// UsersGraphQlSchemaProvider::TYPE_USER de task-php.
const TYPE_USER = "User"

// UsersGraphQlSchemaProvider parte de users en /graphql: users.graphql (define Query, Mutation y User) y sus
// resolvers. El resolver se crea solo si la consulta pide su campo.
type UsersGraphQlSchemaProvider struct{}

func UsersGraphQlSchemaProviderGetInstance() *UsersGraphQlSchemaProvider {
	return &UsersGraphQlSchemaProvider{}
}

func (p *UsersGraphQlSchemaProvider) GetGraphQlFileContent() string {
	return graphqlproviders.GetFileContent("modules/users/infrastructure/providers/users.graphql")
}

func (p *UsersGraphQlSchemaProvider) GetControllerResolvers() []graphqlproviders.GraphQlTypeResolvers {
	return []graphqlproviders.GraphQlTypeResolvers{
		{TypeName: enums.GraphQlEnum.TYPE_QUERY, FieldResolvers: p.getQueryControllers()},
		{TypeName: enums.GraphQlEnum.TYPE_MUTATION, FieldResolvers: p.getMutationControllers()},
	}
}

func (p *UsersGraphQlSchemaProvider) getQueryControllers() []graphqlproviders.GraphQlFieldResolver {
	return []graphqlproviders.GraphQlFieldResolver{
		{FieldName: "users", Resolver: func(h *httprequester.HttpRequester, _ map[string]any, _ map[string]any) (any, error) {
			return controllers.ListUsersGraphQlControllerGetInstance(h).Invoke()
		}},
		{FieldName: "user", Resolver: func(h *httprequester.HttpRequester, gqlPayload map[string]any, _ map[string]any) (any, error) {
			return controllers.GetUserGraphQlControllerGetInstance(h).Invoke(gqlPayload)
		}},
	}
}

func (p *UsersGraphQlSchemaProvider) getMutationControllers() []graphqlproviders.GraphQlFieldResolver {
	return []graphqlproviders.GraphQlFieldResolver{
		{FieldName: "createUser", Resolver: func(h *httprequester.HttpRequester, gqlPayload map[string]any, _ map[string]any) (any, error) {
			return controllers.CreateUserGraphQlControllerGetInstance(h).Invoke(gqlPayload)
		}},
		{FieldName: "logIn", Resolver: func(h *httprequester.HttpRequester, gqlPayload map[string]any, _ map[string]any) (any, error) {
			return controllers.LogInUserGraphQlControllerGetInstance(h).Invoke(gqlPayload)
		}},
		{FieldName: "updateUser", Resolver: func(h *httprequester.HttpRequester, gqlPayload map[string]any, _ map[string]any) (any, error) {
			return controllers.UpdateUserGraphQlControllerGetInstance(h).Invoke(gqlPayload)
		}},
		{FieldName: "deleteUser", Resolver: func(h *httprequester.HttpRequester, gqlPayload map[string]any, _ map[string]any) (any, error) {
			return controllers.DeleteUserGraphQlControllerGetInstance(h).Invoke(gqlPayload)
		}},
		{FieldName: "regenerateApiToken", Resolver: func(h *httprequester.HttpRequester, gqlPayload map[string]any, _ map[string]any) (any, error) {
			return controllers.RegenerateUserApiTokenGraphQlControllerGetInstance(h).Invoke(gqlPayload)
		}},
	}
}
