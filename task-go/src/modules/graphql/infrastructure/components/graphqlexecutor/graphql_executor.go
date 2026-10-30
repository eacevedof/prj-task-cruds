package graphqlexecutor

import (
	"context"
	"fmt"
	"sync"

	graphqlgo "github.com/graph-gophers/graphql-go"
	"github.com/graph-gophers/graphql-go/ast"

	"task-go/src/modules/graphql/domain/enums"
	"task-go/src/modules/graphql/infrastructure/dtos"
	"task-go/src/modules/graphql/infrastructure/exceptions"
	"task-go/src/modules/graphql/infrastructure/schema"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
)

// builtSchema el schema de un registro: se monta y se valida una vez por proceso (graph-gophers lo deja listo para
// ejecutar en paralelo); el httpRequester de cada peticion le llega a los resolvers por el context.
type builtSchema struct {
	once          sync.Once
	graphqlSchema *graphqlgo.Schema
	err           error
}

var builtSchemas sync.Map // *schema.GraphqlSchemaRegistry -> *builtSchema

type httpRequesterKey struct{}

// GraphqlExecutor monta el schema con lo que registran los modulos y ejecuta la peticion con graph-gophers: el unico
// sitio (junto a GraphqlError) que conoce la libreria. Componente: no captura ni loguea.
type GraphqlExecutor struct {
	graphqlSchemaRegistry *schema.GraphqlSchemaRegistry
}

func GraphqlExecutorGetInstance(graphqlSchemaRegistry *schema.GraphqlSchemaRegistry) *GraphqlExecutor {
	return &GraphqlExecutor{graphqlSchemaRegistry: graphqlSchemaRegistry}
}

// Execute los errores de GraphQL (sintaxis, validacion, dominio de un resolver) van en el resultado, no se devuelven.
// error GraphqlSchemaException si el registro esta mal montado (campo repetido o sin resolver).
func (e *GraphqlExecutor) Execute(
	httpRequester *httprequester.HttpRequester,
	graphqlRequestDto *dtos.GraphqlRequestDto,
) (*dtos.GraphqlResultDto, error) {
	graphqlSchema, err := e.getSchema()
	if err != nil {
		return nil, err
	}
	executionResult := graphqlSchema.Exec(
		context.WithValue(context.Background(), httpRequesterKey{}, httpRequester),
		graphqlRequestDto.GetQuery(),
		graphqlRequestDto.GetOperationName(),
		graphqlRequestDto.GetVariables(),
	)
	fillMissingLocations(executionResult.Errors, graphqlRequestDto.GetQuery(), graphqlRequestDto.GetOperationName())
	graphqlErrors := []any{}
	for _, queryError := range executionResult.Errors {
		graphqlErrors = append(graphqlErrors, queryError)
	}
	return dtos.GraphqlResultDtoFromPrimitives(graphqlErrors, executionResult.Data), nil
}

func (e *GraphqlExecutor) getSchema() (*graphqlgo.Schema, error) {
	cachedSchema, _ := builtSchemas.LoadOrStore(e.graphqlSchemaRegistry, &builtSchema{})
	registrySchema := cachedSchema.(*builtSchema)
	registrySchema.once.Do(func() {
		defer func() {
			if recovered := recover(); recovered != nil {
				registrySchema.err = fmt.Errorf("%v", recovered)
			}
		}()
		registrySchema.graphqlSchema, registrySchema.err = e.buildSchema()
	})
	return registrySchema.graphqlSchema, registrySchema.err
}

// buildSchema el BuildSchema + decorador de task-php: el SDL del registro y, por cada campo, su resolver o el de por
// defecto (el valor del padre con ese nombre).
func (e *GraphqlExecutor) buildSchema() (*graphqlgo.Schema, error) {
	resolversByType, err := e.graphqlSchemaRegistry.GetResolversByType()
	if err != nil {
		return nil, err
	}
	graphqlSchema, err := graphqlgo.ParseSchema(
		e.graphqlSchemaRegistry.GetSdl(),
		nil,
		graphqlgo.UseFieldResolvers(),
		graphqlgo.MaxDepth(enums.GraphqlEnum.MAX_QUERY_DEPTH),
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
func failIfRootFieldWithoutResolver(astSchema *ast.Schema, resolversByType map[string]map[string]schema.GraphqlResolver) error {
	for _, rootTypeName := range []string{enums.GraphqlEnum.TYPE_QUERY, enums.GraphqlEnum.TYPE_MUTATION} {
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
