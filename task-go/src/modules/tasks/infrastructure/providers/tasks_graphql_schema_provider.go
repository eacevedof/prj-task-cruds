package providers

import (
	"task-go/src/modules/graphql/domain/enums"
	"task-go/src/modules/graphql/infrastructure/schema"
	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	tasksenums "task-go/src/modules/tasks/domain/enums"
	"task-go/src/modules/tasks/infrastructure/controllers"
	usersenums "task-go/src/modules/users/domain/enums"
)

// TasksGraphqlSchemaProvider parte de tasks en /graphql: tasks.graphql (extiende Query, Mutation y User de users) y
// sus resolvers, incluido User.tasks, que cuelga de User pero es de tasks. Va despues de UsersGraphqlSchemaProvider en
// el registro.
type TasksGraphqlSchemaProvider struct{}

func TasksGraphqlSchemaProviderGetInstance() *TasksGraphqlSchemaProvider {
	return &TasksGraphqlSchemaProvider{}
}

func (p *TasksGraphqlSchemaProvider) GetSdl() string {
	return schema.GetFileContent("modules/tasks/infrastructure/providers/tasks.graphql")
}

func (p *TasksGraphqlSchemaProvider) GetResolversByType() []schema.GraphqlTypeResolvers {
	return []schema.GraphqlTypeResolvers{
		{TypeName: enums.GraphqlEnum.TYPE_QUERY, FieldResolvers: []schema.GraphqlFieldResolver{
			{FieldName: "tasks", Resolver: func(h *httprequester.HttpRequester, args map[string]any, _ map[string]any) (any, error) {
				return controllers.SearchTasksGraphqlControllerGetInstance(h).Invoke(args)
			}},
			{FieldName: "task", Resolver: func(h *httprequester.HttpRequester, args map[string]any, _ map[string]any) (any, error) {
				return controllers.GetTaskGraphqlControllerGetInstance(h).Invoke(args)
			}},
			{FieldName: "userTasks", Resolver: func(h *httprequester.HttpRequester, args map[string]any, _ map[string]any) (any, error) {
				return controllers.SearchTasksByUserGraphqlControllerGetInstance(h).Invoke(
					application.ToString(args[tasksenums.TasksKeyEnum.USER_UUID]),
					args,
				)
			}},
		}},
		{TypeName: enums.GraphqlEnum.TYPE_MUTATION, FieldResolvers: []schema.GraphqlFieldResolver{
			{FieldName: "createTask", Resolver: func(h *httprequester.HttpRequester, args map[string]any, _ map[string]any) (any, error) {
				return controllers.CreateTaskGraphqlControllerGetInstance(h).Invoke(args)
			}},
			{FieldName: "updateTask", Resolver: func(h *httprequester.HttpRequester, args map[string]any, _ map[string]any) (any, error) {
				return controllers.UpdateTaskGraphqlControllerGetInstance(h).Invoke(args)
			}},
			{FieldName: "deleteTask", Resolver: func(h *httprequester.HttpRequester, args map[string]any, _ map[string]any) (any, error) {
				return controllers.DeleteTaskGraphqlControllerGetInstance(h).Invoke(args)
			}},
		}},
		{TypeName: enums.GraphqlEnum.TYPE_USER, FieldResolvers: []schema.GraphqlFieldResolver{
			{FieldName: "tasks", Resolver: func(h *httprequester.HttpRequester, args map[string]any, user map[string]any) (any, error) {
				return controllers.SearchTasksByUserGraphqlControllerGetInstance(h).Invoke(
					application.ToString(user[usersenums.UsersKeyEnum.USER_UUID]),
					args,
				)
			}},
		}},
	}
}
