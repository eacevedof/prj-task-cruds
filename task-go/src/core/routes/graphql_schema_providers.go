package routes

import (
	"sync"

	graphqlproviders "task-go/src/modules/graphql/infrastructure/providers"
	tasksproviders "task-go/src/modules/tasks/infrastructure/providers"
	usersproviders "task-go/src/modules/users/infrastructure/providers"
)

// registerGraphQlSchemaProviders el src/Core/config/graphql_schema_providers.php de task-php: core conoce a todos los
// modulos y le entrega sus providers al registro, que no conoce a ninguno (los modulos dependen de graphql, nunca al
// reves). Vive en core/routes y no en core/config porque core/config lo importan los modulos (config.Tr...) y aqui
// formaria un ciclo de imports; core/routes no lo importa ningun modulo.
// El orden importa: cada modulo nuevo anade aqui su XxxGraphQlSchemaProvider, despues de los modulos cuyos tipos
// extiende.
var registerGraphQlSchemaProviders = sync.OnceFunc(func() {
	graphqlproviders.GraphQlSchemaProvidersRegistryGetInstance().RegisterSchemaProviders(
		[]graphqlproviders.InterfaceGraphQlSchemaProvider{
			usersproviders.UsersGraphQlSchemaProviderGetInstance(),
			tasksproviders.TasksGraphQlSchemaProviderGetInstance(),
		},
	)
})

// LoadGraphQlSchemaProviders lo llaman cmd/api/main.go y tests/bootstrap.go al arrancar (el include_once de
// public/index.php y tests/bootstrap.php). Una sola vez por proceso: registrar dos veces repetiria los campos.
func LoadGraphQlSchemaProviders() {
	registerGraphQlSchemaProviders()
}
