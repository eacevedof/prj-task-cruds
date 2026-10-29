package schema

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"

	"task-go/src/core/boot"
	"task-go/src/modules/graphql/infrastructure/exceptions"
)

// schemaPath la cabecera comun, relativa a src/ (el schema se lee de disco: deuda conocida).
const schemaPath = "modules/graphql/infrastructure/schema/schema.graphql"

// GraphqlSchemaRegistry une lo que registran los modulos: la cabecera comun (schema.graphql) mas el SDL de cada
// proveedor, y la tabla tipo.campo => resolver. Es a /graphql lo que routes.go al router. La lista de proveedores
// se la inyecta core (src/core/routes/graphql_schema.go): este modulo no puede importar core sin ciclo.
type GraphqlSchemaRegistry struct {
	schemaProviders []InterfaceGraphqlSchemaProvider
}

func GraphqlSchemaRegistryGetInstance(schemaProviders []InterfaceGraphqlSchemaProvider) *GraphqlSchemaRegistry {
	return &GraphqlSchemaRegistry{schemaProviders: schemaProviders}
}

// GetSdl en el orden del registro: el modulo que define un tipo va antes que los que lo extienden.
func (r *GraphqlSchemaRegistry) GetSdl() string {
	schemaSdls := []string{GetFileContent(schemaPath)}
	for _, schemaProvider := range r.schemaProviders {
		schemaSdls = append(schemaSdls, schemaProvider.GetSdl())
	}
	return strings.Join(schemaSdls, "\n")
}

// GetResolversByType error GraphqlSchemaException si dos modulos resuelven el mismo campo (uno pisaria al otro sin
// avisar).
func (r *GraphqlSchemaRegistry) GetResolversByType() (map[string]map[string]GraphqlResolver, error) {
	resolversByType := map[string]map[string]GraphqlResolver{}
	for _, schemaProvider := range r.schemaProviders {
		for _, typeResolvers := range schemaProvider.GetResolversByType() {
			if resolversByType[typeResolvers.TypeName] == nil {
				resolversByType[typeResolvers.TypeName] = map[string]GraphqlResolver{}
			}
			resolvers := resolversByType[typeResolvers.TypeName]
			repeatedFields := []string{}
			for _, fieldResolver := range typeResolvers.FieldResolvers {
				if _, isRepeated := resolvers[fieldResolver.FieldName]; isRepeated {
					repeatedFields = append(repeatedFields, fieldResolver.FieldName)
				}
			}
			if len(repeatedFields) > 0 {
				return nil, exceptions.FieldsAlreadyHaveResolver(
					typeResolvers.TypeName,
					repeatedFields,
					fmt.Sprintf("%T", schemaProvider),
				)
			}
			for _, fieldResolver := range typeResolvers.FieldResolvers {
				resolvers[fieldResolver.FieldName] = fieldResolver.Resolver
			}
		}
	}
	return resolversByType, nil
}

// GetFileContent un .graphql relativo a src/ (el file_get_contents de task-php). Si falta es un fallo de despliegue:
// panic, que GraphqlController convierte en 500.
func GetFileContent(pathFromSrc string) string {
	content, err := os.ReadFile(filepath.Join(boot.BootPathsEnum.PATH_SRC, pathFromSrc))
	if err != nil {
		panic(err)
	}
	return string(content)
}
