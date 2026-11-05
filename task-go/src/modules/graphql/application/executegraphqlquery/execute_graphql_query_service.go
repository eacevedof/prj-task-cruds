package executegraphqlquery

import (
	"context"
	"fmt"
	"sync"

	graphqlgo "github.com/graph-gophers/graphql-go"
	"github.com/graph-gophers/graphql-go/ast"

	"task-go/src/modules/graphql/domain/enums"
	"task-go/src/modules/graphql/infrastructure/exceptions"
	"task-go/src/modules/graphql/infrastructure/providers"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
)

// builtSchema el schema de un registro: se monta y se valida una vez por proceso (graph-gophers lo deja listo para
// ejecutar en paralelo); el httpRequester de cada peticion le llega a los resolvers por el context.
type builtSchema struct {
	once          sync.Once
	graphqlSchema *graphqlgo.Schema
	err           error
}

var builtSchemas sync.Map // *providers.GraphQlSchemaProvidersRegistry -> *builtSchema

type httpRequesterKey struct{}

// ExecuteGraphQlQueryService monta el schema con lo que registran los modulos y ejecuta la peticion con graph-gophers:
// el unico sitio (junto a GraphQlException) que conoce la libreria. Servicio: no captura ni loguea.
type ExecuteGraphQlQueryService struct {
	graphqlSchemaRegistry *providers.GraphQlSchemaProvidersRegistry
}

// ExecuteGraphQlQueryServiceGetInstance graphqlSchemaRegistry lo monta core con la lista de providers (ver
// GraphQlSchemaProvidersRegistry).
func ExecuteGraphQlQueryServiceGetInstance(
	graphqlSchemaRegistry *providers.GraphQlSchemaProvidersRegistry,
) *ExecuteGraphQlQueryService {
	return &ExecuteGraphQlQueryService{graphqlSchemaRegistry: graphqlSchemaRegistry}
}

// Invoke los errores de GraphQL (sintaxis, validacion, dominio de un resolver) van en el resultado, no se devuelven.
// error GraphQlSchemaException si el registro esta mal montado (campo repetido o sin resolver).
func (s *ExecuteGraphQlQueryService) Invoke(
	httpRequester *httprequester.HttpRequester,
	executeGraphQlRequestDto *ExecuteGraphQlRequestDto,
) (*ExecuteGraphQlResultDto, error) {
	graphqlSchema, err := s.getSchema()
	if err != nil {
		return nil, err
	}
	executionResult := graphqlSchema.Exec(
		context.WithValue(context.Background(), httpRequesterKey{}, httpRequester),
		executeGraphQlRequestDto.GetQuery(),
		executeGraphQlRequestDto.GetOperationName(),
		executeGraphQlRequestDto.GetVariables(),
	)
	fillMissingLocations(
		executionResult.Errors,
		executeGraphQlRequestDto.GetQuery(),
		executeGraphQlRequestDto.GetOperationName(),
	)
	graphqlErrors := []any{}
	for _, queryError := range executionResult.Errors {
		graphqlErrors = append(graphqlErrors, queryError)
	}
	return ExecuteGraphQlResultDtoFromPrimitives(graphqlErrors, executionResult.Data), nil
}

func (s *ExecuteGraphQlQueryService) getSchema() (*graphqlgo.Schema, error) {
	cachedSchema, _ := builtSchemas.LoadOrStore(s.graphqlSchemaRegistry, &builtSchema{})
	registrySchema := cachedSchema.(*builtSchema)
	registrySchema.once.Do(func() {
		defer func() {
			if recovered := recover(); recovered != nil {
				registrySchema.err = fmt.Errorf("%v", recovered)
			}
		}()
		registrySchema.graphqlSchema, registrySchema.err = s.buildSchema()
	})
	return registrySchema.graphqlSchema, registrySchema.err
}

// buildSchema el BuildSchema + decorador de task-php: todos los esquemas .graphql de los modulos, en el orden del
// registro, con la cabecera comun schema.graphql y, por cada campo, su resolver o el de por defecto (el valor del
// padre con ese nombre).
func (s *ExecuteGraphQlQueryService) buildSchema() (*graphqlgo.Schema, error) {
	resolversByType, err := s.graphqlSchemaRegistry.GetAllMergedResolversByType()
	if err != nil {
		return nil, err
	}
	graphqlSchema, err := graphqlgo.ParseSchema(
		s.graphqlSchemaRegistry.GetGraphQlSchemaAsString(),
		nil,
		graphqlgo.UseFieldResolvers(),
		graphqlgo.MaxDepth(enums.GraphQlEnum.MAX_QUERY_DEPTH),
	)
	if err != nil {
		return nil, err
	}
	if err := failIfRootFieldWithoutResolver(graphqlSchema.AST(), resolversByType); err != nil {
		return nil, err
	}
	rootResolver, err := newResolverBuilder(graphqlSchema.AST(), resolversByType).getRootResolver()
	if err != nil {
		return nil, err
	}
	if err := graphqlSchema.ApplyResolver(rootResolver); err != nil {
		return nil, err
	}
	return graphqlSchema, nil
}

// failIfRootFieldWithoutResolver un campo de Query o Mutation sin resolver devolveria null sin avisar: es un fallo del
// registro.
func failIfRootFieldWithoutResolver(
	astSchema *ast.Schema,
	resolversByType map[string]map[string]providers.GraphQlResolver,
) error {
	for _, rootTypeName := range []string{enums.GraphQlEnum.TYPE_QUERY, enums.GraphQlEnum.TYPE_MUTATION} {
		rootType, isObject := astSchema.Types[rootTypeName].(*ast.ObjectTypeDefinition)
		if !isObject {
			continue
		}
		fieldsWithoutResolver := []string{}
		for _, field := range rootType.Fields {
			if _, hasResolver := resolversByType[rootTypeName][field.Name]; !hasResolver {
				fieldsWithoutResolver = append(fieldsWithoutResolver, field.Name)
			}
		}
		if len(fieldsWithoutResolver) > 0 {
			return exceptions.FieldsWithoutResolver(rootTypeName, fieldsWithoutResolver)
		}
	}
	return nil
}

func getHttpRequester(ctx context.Context) *httprequester.HttpRequester {
	httpRequester, _ := ctx.Value(httpRequesterKey{}).(*httprequester.HttpRequester)
	return httpRequester
}
