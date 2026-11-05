package routes

import (
	"sync"

	graphqlproviders "task-go/src/modules/graphql/infrastructure/providers"
	tasksproviders "task-go/src/modules/tasks/infrastructure/providers"
	usersproviders "task-go/src/modules/users/infrastructure/providers"
)

// graphqlSchemaProvidersRegistry el GraphQlSchemaProvidersRegistry::getAllModulesProviders() de task-php: los
// providers de /graphql, que se montan la primera vez que llega una peticion a /graphql. En Go la lista vive en core y
// se inyecta en el registro porque el modulo graphql no puede importar los providers de users y tasks (ciclo de
// imports: ellos importan graphql/infrastructure/providers por InterfaceGraphQlSchemaProvider).
// El orden importa: cada modulo nuevo anade aqui su XxxGraphQlSchemaProvider, despues de los modulos cuyos tipos
// extiende.
var graphqlSchemaProvidersRegistry = sync.OnceValue(func() *graphqlproviders.GraphQlSchemaProvidersRegistry {
	return graphqlproviders.GraphQlSchemaProvidersRegistryGetInstance([]graphqlproviders.InterfaceGraphQlSchemaProvider{
		usersproviders.UsersGraphQlSchemaProviderGetInstance(),
		tasksproviders.TasksGraphQlSchemaProviderGetInstance(),
	})
})

func GetGraphQlSchemaProvidersRegistry() *graphqlproviders.GraphQlSchemaProvidersRegistry {
	return graphqlSchemaProvidersRegistry()
}
