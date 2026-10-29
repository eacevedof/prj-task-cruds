package routes

import (
	"sync"

	"task-go/src/modules/graphql/infrastructure/schema"
	tasksproviders "task-go/src/modules/tasks/infrastructure/providers"
	usersproviders "task-go/src/modules/users/infrastructure/providers"
)

// graphqlSchemaRegistry el graphql_schema.php de task-php: el registro de los modulos en /graphql, que se monta la
// primera vez que llega una peticion a /graphql. Vive en core y se inyecta en el registro porque el modulo Graphql no
// puede importar core (ciclo de imports: los XxxGraphqlController ya importan core/config a traves de su base).
// Cada modulo nuevo anade aqui su XxxGraphqlSchemaProvider, despues de los modulos cuyos tipos extiende.
var graphqlSchemaRegistry = sync.OnceValue(func() *schema.GraphqlSchemaRegistry {
	return schema.GraphqlSchemaRegistryGetInstance([]schema.InterfaceGraphqlSchemaProvider{
		usersproviders.UsersGraphqlSchemaProviderGetInstance(),
		tasksproviders.TasksGraphqlSchemaProviderGetInstance(),
	})
})

func GetGraphqlSchemaRegistry() *schema.GraphqlSchemaRegistry {
	return graphqlSchemaRegistry()
}
