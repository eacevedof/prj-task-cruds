package modules.graphql.infrastructure.components;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.CompletableFuture;
import java.util.concurrent.CompletionException;
import java.util.function.BiFunction;

import graphql.ExecutionInput;
import graphql.ExecutionResult;
import graphql.GraphQL;
import graphql.GraphQLError;
import graphql.GraphqlErrorBuilder;
import graphql.ParseAndValidate;
import graphql.ParseAndValidateResult;
import graphql.execution.DataFetcherExceptionHandler;
import graphql.execution.DataFetcherExceptionHandlerParameters;
import graphql.execution.DataFetcherExceptionHandlerResult;
import graphql.language.SourceLocation;
import graphql.schema.DataFetcher;
import graphql.schema.GraphQLFieldDefinition;
import graphql.schema.GraphQLObjectType;
import graphql.schema.GraphQLSchema;
import graphql.schema.idl.FieldWiringEnvironment;
import graphql.schema.idl.RuntimeWiring;
import graphql.schema.idl.SchemaGenerator;
import graphql.schema.idl.SchemaParser;
import graphql.schema.idl.WiringFactory;

import modules.shared.application.Primitives;

import modules.graphql.domain.enums.GraphqlEnum;
import modules.graphql.infrastructure.dtos.GraphqlRequestDto;
import modules.graphql.infrastructure.dtos.GraphqlResultDto;
import modules.graphql.infrastructure.exceptions.GraphqlSchemaException;
import modules.graphql.infrastructure.schema.GraphqlSchemaRegistry;
import modules.graphql.infrastructure.validators.QueryDepthRule;

/**
 * Monta el schema con lo que registran los modulos y ejecuta la peticion con graphql-java: el unico sitio (junto a
 * AbstractGraphqlController y QueryDepthRule) que conoce la libreria. Componente: no captura ni loguea.
 * El schema se construye una vez por proceso, en la primera peticion a /graphql: los resolvers son perezosos (crean su
 * XxxGraphqlController al llamarse) y valen para todas las peticiones. Si el registro esta mal montado no se guarda
 * nada y cada peticion vuelve a fallar con el mismo GraphqlSchemaException.
 */
public final class GraphqlExecutor {

    private static GraphqlExecutor instance;

    private final GraphqlSchemaRegistry graphqlSchemaRegistry;
    private GraphQL graphql;

    private GraphqlExecutor(GraphqlSchemaRegistry graphqlSchemaRegistry) {
        this.graphqlSchemaRegistry = graphqlSchemaRegistry;
    }

    public static synchronized GraphqlExecutor getInstance() {
        if (instance == null) {
            instance = new GraphqlExecutor(GraphqlSchemaRegistry.getInstance());
        }
        return instance;
    }

    /**
     * El GraphQL::executeQuery()->toArray() de webonyx: parse + validacion (todas las reglas) + profundidad maxima +
     * ejecucion. Los errores de GraphQL (sintaxis, validacion, dominio de un resolver) van en el resultado, no se lanzan.
     * @throws GraphqlSchemaException si el registro esta mal montado (campo repetido o sin resolver)
     */
    public GraphqlResultDto execute(GraphqlRequestDto graphqlRequestDto) {
        ExecutionInput executionInput = ExecutionInput.newExecutionInput()
            .query(graphqlRequestDto.getQuery())
            .variables(graphqlRequestDto.getVariables())
            .operationName(graphqlRequestDto.getOperationName())
            .build();

        GraphQL graphQL = getGraphql();
        ParseAndValidateResult parseAndValidateResult = ParseAndValidate.parseAndValidate(graphQL.getGraphQLSchema(), executionInput);
        if (parseAndValidateResult.isFailure()) {
            return getGraphqlResultDto(parseAndValidateResult.getErrors(), null);
        }

        List<GraphQLError> depthErrors = QueryDepthRule.getInstance(GraphqlEnum.MAX_QUERY_DEPTH)
            .getErrors(parseAndValidateResult.getDocument());
        if (!depthErrors.isEmpty()) {
            return getGraphqlResultDto(depthErrors, null);
        }

        ExecutionResult executionResult = graphQL.execute(executionInput);
        return getGraphqlResultDto(executionResult.getErrors(), executionResult.getData());
    }

    private static GraphqlResultDto getGraphqlResultDto(List<GraphQLError> errors, Object data) {
        Map<String, Object> graphqlResult = new LinkedHashMap<>();
        graphqlResult.put(GraphqlEnum.KEY_ERRORS, errors.stream().map(GraphqlExecutor::getErrorAsArray).toList());
        graphqlResult.put(GraphqlEnum.KEY_DATA, data);
        return GraphqlResultDto.fromPrimitives(graphqlResult);
    }

    /**
     * El error como lo serializa webonyx: message, locations, path y extensions, cada uno solo si tiene algo (sin la
     * "classification" que graphql-java mete en extensions).
     */
    private static Map<String, Object> getErrorAsArray(GraphQLError error) {
        Map<String, Object> errorArray = new LinkedHashMap<>();
        errorArray.put(GraphqlEnum.KEY_MESSAGE, error.getMessage());
        List<SourceLocation> locations = error.getLocations();
        if (locations != null && !locations.isEmpty()) {
            errorArray.put(GraphqlEnum.KEY_LOCATIONS, locations.stream()
                .map(location -> Primitives.of("line", location.getLine(), "column", location.getColumn()))
                .toList());
        }
        if (error.getPath() != null && !error.getPath().isEmpty()) {
            errorArray.put(GraphqlEnum.KEY_PATH, error.getPath());
        }
        if (error.getExtensions() != null && !error.getExtensions().isEmpty()) {
            errorArray.put(GraphqlEnum.KEY_EXTENSIONS, error.getExtensions());
        }
        return errorArray;
    }

    /**
     * @throws GraphqlSchemaException
     */
    private synchronized GraphQL getGraphql() {
        if (graphql == null) {
            Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> resolversByType =
                graphqlSchemaRegistry.getResolversByType();
            GraphQLSchema graphqlSchema = new SchemaGenerator().makeExecutableSchema(
                new SchemaParser().parse(graphqlSchemaRegistry.getSdl()),
                getRuntimeWiring(resolversByType)
            );
            failIfRootFieldWithoutResolver(graphqlSchema, resolversByType);
            graphql = GraphQL.newGraphQL(graphqlSchema)
                .defaultDataFetcherExceptionHandler(getDataFetcherExceptionHandler())
                .build();
        }
        return graphql;
    }

    /**
     * Todos los campos pasan por getFieldValue(): el typeConfigDecorator de task-php.
     */
    private static RuntimeWiring getRuntimeWiring(
        Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> resolversByType
    ) {
        return RuntimeWiring.newRuntimeWiring()
            .wiringFactory(new WiringFactory() {
                @Override
                public boolean providesDataFetcher(FieldWiringEnvironment environment) {
                    return true;
                }

                @Override
                public DataFetcher<?> getDataFetcher(FieldWiringEnvironment environment) {
                    String fieldName = environment.getFieldDefinition().getName();
                    BiFunction<Map<String, Object>, Map<String, Object>, Object> resolver = resolversByType
                        .getOrDefault(environment.getParentType().getName(), Map.of())
                        .get(fieldName);
                    return dataFetchingEnvironment -> getFieldValue(
                        resolver,
                        fieldName,
                        dataFetchingEnvironment.getArguments(),
                        Primitives.getPrimitives(dataFetchingEnvironment.getSource())
                    );
                }
            })
            .build();
    }

    /**
     * El resolver del tipo.campo si lo hay; si no, el campo sale del mapa del ResultDto (resolver por defecto).
     */
    private static Object getFieldValue(
        BiFunction<Map<String, Object>, Map<String, Object>, Object> resolver,
        String fieldName,
        Map<String, Object> args,
        Map<String, Object> parent
    ) {
        return resolver != null ? resolver.apply(args, parent) : parent.get(fieldName);
    }

    /**
     * Un campo de Query o Mutation sin resolver devolveria null sin avisar: es un fallo del registro.
     * @throws GraphqlSchemaException
     */
    private static void failIfRootFieldWithoutResolver(
        GraphQLSchema graphqlSchema,
        Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> resolversByType
    ) {
        for (String rootTypeName : List.of(GraphqlEnum.TYPE_QUERY, GraphqlEnum.TYPE_MUTATION)) {
            GraphQLObjectType rootType = graphqlSchema.getObjectType(rootTypeName);
            if (rootType == null) continue;

            Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> rootResolvers =
                resolversByType.getOrDefault(rootTypeName, Map.of());
            List<String> fieldsWithoutResolver = rootType.getFieldDefinitions().stream()
                .map(GraphQLFieldDefinition::getName)
                .filter(fieldName -> !rootResolvers.containsKey(fieldName))
                .toList();
            if (!fieldsWithoutResolver.isEmpty()) {
                throw GraphqlSchemaException.fieldsWithoutResolver(rootTypeName, fieldsWithoutResolver);
            }
        }
    }

    /**
     * Un resolver que lanza deja su campo a null y el error va a errors[] con su path y locations: el mensaje y las
     * extensions del GraphqlErrorException tal cual (sin el "Exception while fetching data" de graphql-java).
     */
    private static DataFetcherExceptionHandler getDataFetcherExceptionHandler() {
        return new DataFetcherExceptionHandler() {
            @Override
            public CompletableFuture<DataFetcherExceptionHandlerResult> handleException(
                DataFetcherExceptionHandlerParameters handlerParameters
            ) {
                Throwable exception = handlerParameters.getException();
                if (exception instanceof CompletionException && exception.getCause() != null) {
                    exception = exception.getCause();
                }
                GraphqlErrorBuilder<?> graphqlErrorBuilder = GraphqlErrorBuilder.newError(handlerParameters.getDataFetchingEnvironment())
                    .message(exception.getMessage());
                if (exception instanceof GraphQLError graphqlError && graphqlError.getExtensions() != null) {
                    graphqlErrorBuilder.extensions(graphqlError.getExtensions());
                }
                return CompletableFuture.completedFuture(
                    DataFetcherExceptionHandlerResult.newResult(graphqlErrorBuilder.build()).build()
                );
            }
        };
    }
}
