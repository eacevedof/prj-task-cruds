package providers

import (
	"task-go/src/modules/graphql/domain/enums"
	graphqlproviders "task-go/src/modules/graphql/infrastructure/providers"
	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/tasks/infrastructure/controllers"
	usersenums "task-go/src/modules/users/domain/enums"
	usersproviders "task-go/src/modules/users/infrastructure/providers"
)

// TasksGraphQlSchemaProvider parte de tasks en /graphql: tasks.graphql (extiende Query, Mutation y User de users) y
// sus resolvers, incluido User.tasks, que cuelga de User pero es de tasks. Va despues de UsersGraphQlSchemaProvider en
// el registro.
type TasksGraphQlSchemaProvider struct{}

func TasksGraphQlSchemaProviderGetInstance() *TasksGraphQlSchemaProvider {
	return &TasksGraphQlSchemaProvider{}
}

func (p *TasksGraphQlSchemaProvider) GetGraphQlFileContent() string {
	return graphqlproviders.GetFileContent("modules/tasks/infrastructure/providers/tasks.graphql")
}

func (p *TasksGraphQlSchemaProvider) GetControllerResolvers() []graphqlproviders.GraphQlTypeResolvers {
	return []graphqlproviders.GraphQlTypeResolvers{
		{TypeName: enums.GraphQlEnum.TYPE_QUERY, FieldResolvers: p.getQueryControllers()},

		{TypeName: enums.GraphQlEnum.TYPE_MUTATION, FieldResolvers: p.getMutationControllers()},

		// Las tareas de un usuario.
		// User.tasks: solo ejemplo de extender un tipo de otro modulo, no lo consume ningun front (ver tasks.graphql)
		{TypeName: usersproviders.TYPE_USER, FieldResolvers: []graphqlproviders.GraphQlFieldResolver{
			{FieldName: "tasks", Resolver: func(h *httprequester.HttpRequester, gqlPayload map[string]any, user map[string]any) (any, error) {
				return controllers.SearchTasksByUserGraphQlControllerGetInstance(h).Invoke(
					application.ToString(user[usersenums.UsersKeyEnum.USER_UUID]),
					gqlPayload,
				)
			}},
		}},
	}
}

func (p *TasksGraphQlSchemaProvider) getQueryControllers() []graphqlproviders.GraphQlFieldResolver {
	return []graphqlproviders.GraphQlFieldResolver{
		{FieldName: "tasks", Resolver: func(h *httprequester.HttpRequester, gqlPayload map[string]any, _ map[string]any) (any, error) {
			return controllers.SearchTasksGraphQlControllerGetInstance(h).Invoke(gqlPayload)
		}},
		{FieldName: "task", Resolver: func(h *httprequester.HttpRequester, gqlPayload map[string]any, _ map[string]any) (any, error) {
			return controllers.GetTaskGraphQlControllerGetInstance(h).Invoke(gqlPayload)
		}},
	}
}

func (p *TasksGraphQlSchemaProvider) getMutationControllers() []graphqlproviders.GraphQlFieldResolver {
	return []graphqlproviders.GraphQlFieldResolver{
		{FieldName: "createTask", Resolver: func(h *httprequester.HttpRequester, gqlPayload map[string]any, _ map[string]any) (any, error) {
			return controllers.CreateTaskGraphQlControllerGetInstance(h).Invoke(gqlPayload)
		}},
		{FieldName: "updateTask", Resolver: func(h *httprequester.HttpRequester, gqlPayload map[string]any, _ map[string]any) (any, error) {
			return controllers.UpdateTaskGraphQlControllerGetInstance(h).Invoke(gqlPayload)
		}},
		{FieldName: "deleteTask", Resolver: func(h *httprequester.HttpRequester, gqlPayload map[string]any, _ map[string]any) (any, error) {
			return controllers.DeleteTaskGraphQlControllerGetInstance(h).Invoke(gqlPayload)
		}},
	}
}
