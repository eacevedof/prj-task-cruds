package providers

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"

	"task-go/src/core/boot"
	"task-go/src/modules/graphql/infrastructure/exceptions"
)

// schemaPath la cabecera comun, relativa a src/ (el schema se lee de disco: deuda conocida).
const schemaPath = "modules/graphql/infrastructure/providers/schema.graphql"

// GraphQlSchemaProvidersRegistry une lo que le registran los modulos: la cabecera comun (schema.graphql) mas el SDL
// de cada proveedor, y la tabla tipo.campo => resolver. No conoce a ningun modulo: es core quien le registra los
// providers al arrancar (src/core/routes/graphql_schema_providers.go, el graphql_schema_providers.php de task-php).
type GraphQlSchemaProvidersRegistry struct {
	schemaProvidersInstances []InterfaceGraphQlSchemaProvider
}

var graphqlSchemaProvidersRegistry = &GraphQlSchemaProvidersRegistry{}

func GraphQlSchemaProvidersRegistryGetInstance() *GraphQlSchemaProvidersRegistry {
	return graphqlSchemaProvidersRegistry
}

// RegisterSchemaProviders el orden importa: primero los que definen un tipo, despues los que lo extienden. Se llama
// al arrancar, antes de servir peticiones. task-php guarda las clases y las instancia en cada peticion; aqui los
// providers son structs vacios y se guardan ya instanciados.
func (r *GraphQlSchemaProvidersRegistry) RegisterSchemaProviders(
	schemaProvidersInstances []InterfaceGraphQlSchemaProvider,
) {
	r.schemaProvidersInstances = append(r.schemaProvidersInstances, schemaProvidersInstances...)
}

// GetGraphQlSchemaAsString en el orden del registro: el modulo que define un tipo va antes que los que lo extienden.
func (r *GraphQlSchemaProvidersRegistry) GetGraphQlSchemaAsString() string {
	allSchemas := []string{GetFileContent(schemaPath)}
	for _, schemaProviderInstance := range r.schemaProvidersInstances {
		allSchemas = append(allSchemas, schemaProviderInstance.GetGraphQlFileContent())
	}
	return strings.Join(allSchemas, "\n")
}

// GetAllMergedResolversByType une los GetControllerResolvers() de cada provider en una sola tabla tipo => campo =>
// resolver. Cada resolver crea su controller solo si la consulta pide ese campo.
//
// Con el registro actual (Users, Tasks) devuelve:
//
//	"Query"    => users, user (Users) · tasks, task (Tasks)
//	"Mutation" => createUser, logIn, updateUser, deleteUser, regenerateApiToken (Users) · createTask, updateTask,
//	              deleteTask (Tasks)
//	"User"     => tasks (Tasks: SearchTasksByUserGraphQlController)
//
// "Query.tasks" y "User.tasks" no chocan: la clave de choque es tipo + campo, no solo el campo.
// error GraphQlSchemaException si dos modulos resuelven el mismo campo (uno pisaria al otro sin avisar).
func (r *GraphQlSchemaProvidersRegistry) GetAllMergedResolversByType() (map[string]map[string]GraphQlResolver, error) {
	resolversByType := map[string]map[string]GraphQlResolver{}
	for _, schemaProviderInstance := range r.schemaProvidersInstances {
		for _, controllerResolvers := range schemaProviderInstance.GetControllerResolvers() {
			if resolversByType[controllerResolvers.TypeName] == nil {
				resolversByType[controllerResolvers.TypeName] = map[string]GraphQlResolver{}
			}
			resolvers := resolversByType[controllerResolvers.TypeName]
			repeatedFields := []string{}
			for _, fieldResolver := range controllerResolvers.FieldResolvers {
				if _, isRepeated := resolvers[fieldResolver.FieldName]; isRepeated {
					repeatedFields = append(repeatedFields, fieldResolver.FieldName)
				}
			}
			if len(repeatedFields) > 0 {
				return nil, exceptions.FieldsAlreadyHaveResolver(
					controllerResolvers.TypeName,
					repeatedFields,
					fmt.Sprintf("%T", schemaProviderInstance),
				)
			}
			for _, fieldResolver := range controllerResolvers.FieldResolvers {
				resolvers[fieldResolver.FieldName] = fieldResolver.Resolver
			}
		}
	}
	return resolversByType, nil
}

// GetFileContent un .graphql relativo a src/ (el file_get_contents de task-php). Si falta es un fallo de despliegue:
// panic, que ExecuteGraphQlQueryController convierte en 500.
func GetFileContent(pathFromSrc string) string {
	content, err := os.ReadFile(filepath.Join(boot.BootPathsEnum.PATH_SRC, pathFromSrc))
	if err != nil {
		panic(err)
	}
	return string(content)
}
