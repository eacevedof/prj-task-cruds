package providers

import (
	"task-go/src/modules/graphql/domain/enums"
	"task-go/src/modules/graphql/infrastructure/schema"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/users/infrastructure/controllers"
)

// UsersGraphqlSchemaProvider parte de users en /graphql: users.graphql (define Query, Mutation y User) y sus
// resolvers. El resolver se crea solo si la consulta pide su campo.
type UsersGraphqlSchemaProvider struct{}

func UsersGraphqlSchemaProviderGetInstance() *UsersGraphqlSchemaProvider {
	return &UsersGraphqlSchemaProvider{}
}

func (p *UsersGraphqlSchemaProvider) GetSdl() string {
	return schema.GetFileContent("modules/users/infrastructure/providers/users.graphql")
}

func (p *UsersGraphqlSchemaProvider) GetResolversByType() []schema.GraphqlTypeResolvers {
	return []schema.GraphqlTypeResolvers{
		{TypeName: enums.GraphqlEnum.TYPE_QUERY, FieldResolvers: []schema.GraphqlFieldResolver{
			{FieldName: "users", Resolver: func(h *httprequester.HttpRequester, _ map[string]any, _ map[string]any) (any, error) {
				return controllers.ListUsersGraphqlControllerGetInstance(h).Invoke()
			}},
			{FieldName: "user", Resolver: func(h *httprequester.HttpRequester, args map[string]any, _ map[string]any) (any, error) {
				return controllers.GetUserGraphqlControllerGetInstance(h).Invoke(args)
			}},
		}},
		{TypeName: enums.GraphqlEnum.TYPE_MUTATION, FieldResolvers: []schema.GraphqlFieldResolver{
			{FieldName: "createUser", Resolver: func(h *httprequester.HttpRequester, args map[string]any, _ map[string]any) (any, error) {
				return controllers.CreateUserGraphqlControllerGetInstance(h).Invoke(args)
			}},
			{FieldName: "logIn", Resolver: func(h *httprequester.HttpRequester, args map[string]any, _ map[string]any) (any, error) {
				return controllers.LogInUserGraphqlControllerGetInstance(h).Invoke(args)
			}},
			{FieldName: "updateUser", Resolver: func(h *httprequester.HttpRequester, args map[string]any, _ map[string]any) (any, error) {
				return controllers.UpdateUserGraphqlControllerGetInstance(h).Invoke(args)
			}},
			{FieldName: "deleteUser", Resolver: func(h *httprequester.HttpRequester, args map[string]any, _ map[string]any) (any, error) {
				return controllers.DeleteUserGraphqlControllerGetInstance(h).Invoke(args)
			}},
			{FieldName: "regenerateApiToken", Resolver: func(h *httprequester.HttpRequester, args map[string]any, _ map[string]any) (any, error) {
				return controllers.RegenerateUserApiTokenGraphqlControllerGetInstance(h).Invoke(args)
			}},
		}},
	}
}
