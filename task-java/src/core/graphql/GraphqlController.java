package core.graphql;

import java.io.IOException;
import java.io.InputStream;
import java.io.UncheckedIOException;
import java.nio.charset.StandardCharsets;
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
import graphql.schema.DataFetchingEnvironment;
import graphql.schema.idl.FieldWiringEnvironment;
import graphql.schema.idl.RuntimeWiring;
import graphql.schema.idl.SchemaGenerator;
import graphql.schema.idl.SchemaParser;
import graphql.schema.idl.WiringFactory;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.domain.enums.GraphqlEnum;
import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;
import modules.shared.infrastructure.components.json.JsonSerializer;
import modules.shared.infrastructure.controllers.AbstractApiController;

import modules.users.domain.enums.UsersKeyEnum;
import modules.users.infrastructure.controllers.CreateUserApiController;
import modules.users.infrastructure.controllers.DeleteUserApiController;
import modules.users.infrastructure.controllers.GetUserApiController;
import modules.users.infrastructure.controllers.ListUsersApiController;
import modules.users.infrastructure.controllers.LogInUserApiController;
import modules.users.infrastructure.controllers.RegenerateUserApiTokenApiController;
import modules.users.infrastructure.controllers.UpdateUserApiController;

import modules.tasks.domain.enums.TasksKeyEnum;
import modules.tasks.infrastructure.controllers.CreateTaskApiController;
import modules.tasks.infrastructure.controllers.DeleteTaskApiController;
import modules.tasks.infrastructure.controllers.GetTaskApiController;
import modules.tasks.infrastructure.controllers.SearchTasksApiController;
import modules.tasks.infrastructure.controllers.UpdateTaskApiController;

import static core.config.Global.__;

/**
 * POST /graphql  body: {query, variables?, operationName?}  header: Authorization: Bearer <api_token>
 * (cada resolver autentica; sin token solo responden createUser y logIn).
 * Vive en Core y no en Shared porque cablea los resolvers de varios modulos (Shared no depende de ellos).
 * La respuesta es la de GraphQL ({errors?, data}), no el envelope REST: un error de dominio va en
 * errors[].extensions (ver AbstractGraphqlController) con status 200, como manda GraphQL sobre HTTP.
 */
public final class GraphqlController extends AbstractApiController {

    /**
     * Tipo de schema.graphql => campo => fn(args, parent).
     */
    private static final String CONTEXT_KEY_RESOLVERS_BY_TYPE = "resolversByType";

    // el schema se construye una vez por proceso; los resolvers de cada peticion le llegan en el GraphQLContext
    private static GraphQL graphql;

    private final ListUsersApiController listUsersApiController;
    private final GetUserApiController getUserApiController;
    private final CreateUserApiController createUserApiController;
    private final LogInUserApiController logInUserApiController;
    private final UpdateUserApiController updateUserApiController;
    private final DeleteUserApiController deleteUserApiController;
    private final RegenerateUserApiTokenApiController regenerateUserApiTokenApiController;
    private final SearchTasksApiController searchTasksApiController;
    private final GetTaskApiController getTaskApiController;
    private final CreateTaskApiController createTaskApiController;
    private final UpdateTaskApiController updateTaskApiController;
    private final DeleteTaskApiController deleteTaskApiController;

    public GraphqlController() {
        this.listUsersApiController = ListUsersApiController.getInstance();
        this.getUserApiController = GetUserApiController.getInstance();
        this.createUserApiController = CreateUserApiController.getInstance();
        this.logInUserApiController = LogInUserApiController.getInstance();
        this.updateUserApiController = UpdateUserApiController.getInstance();
        this.deleteUserApiController = DeleteUserApiController.getInstance();
        this.regenerateUserApiTokenApiController = RegenerateUserApiTokenApiController.getInstance();
        this.searchTasksApiController = SearchTasksApiController.getInstance();
        this.getTaskApiController = GetTaskApiController.getInstance();
        this.createTaskApiController = CreateTaskApiController.getInstance();
        this.updateTaskApiController = UpdateTaskApiController.getInstance();
        this.deleteTaskApiController = DeleteTaskApiController.getInstance();
    }

    public static GraphqlController getInstance() {
        return new GraphqlController();
    }

    public HttpResponder execute() {
        String query = httpRequest.getPostString(GraphqlEnum.KEY_QUERY);
        if (query.isEmpty()) {
            return getGraphqlErrorResponse(
                ExceptionEnum.CODE_BAD_REQUEST,
                __(SharedMessageEnum.GRAPHQL_QUERY_IS_REQUIRED)
            );
        }

        try {
            return getGraphqlResponse(ExceptionEnum.CODE_OK, getExecutionResult(query));
        }
        catch (Exception e) {
            logTrait.logException(e, "GraphqlController::execute");
            return getGraphqlErrorResponse(
                ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
                __(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)
            );
        }
    }

    /**
     * El GraphQL::executeQuery()->toArray() de webonyx: parse + validacion + profundidad maxima + ejecucion.
     * errors (si hay) delante de data, y data solo si no es null.
     */
    private Map<String, Object> getExecutionResult(String query) {
        String operationName = httpRequest.getPostString(GraphqlEnum.KEY_OPERATION_NAME);
        ExecutionInput executionInput = ExecutionInput.newExecutionInput()
            .query(query)
            .variables(getVariables())
            .operationName(operationName.isEmpty() ? null : operationName)
            .graphQLContext(Map.of(CONTEXT_KEY_RESOLVERS_BY_TYPE, getResolversByType()))
            .build();

        GraphQL graphQL = getGraphql();
        ParseAndValidateResult parseAndValidateResult = ParseAndValidate.parseAndValidate(graphQL.getGraphQLSchema(), executionInput);
        if (parseAndValidateResult.isFailure()) {
            return getGraphqlResult(parseAndValidateResult.getErrors(), null);
        }

        List<GraphQLError> depthErrors = QueryDepthRule.getInstance(GraphqlEnum.MAX_QUERY_DEPTH)
            .getErrors(parseAndValidateResult.getDocument());
        if (!depthErrors.isEmpty()) {
            return getGraphqlResult(depthErrors, null);
        }

        ExecutionResult executionResult = graphQL.execute(executionInput);
        return getGraphqlResult(executionResult.getErrors(), executionResult.getData());
    }

    private Map<String, Object> getGraphqlResult(List<GraphQLError> errors, Object data) {
        Map<String, Object> graphqlResult = new LinkedHashMap<>();
        if (!errors.isEmpty()) {
            graphqlResult.put(GraphqlEnum.KEY_ERRORS, errors.stream().map(this::getErrorAsArray).toList());
        }
        if (data != null) {
            graphqlResult.put(GraphqlEnum.KEY_DATA, data);
        }
        return graphqlResult;
    }

    /**
     * El error como lo serializa webonyx: message, locations, path y extensions, cada uno solo si tiene algo (sin la
     * "classification" que graphql-java mete en extensions).
     */
    private Map<String, Object> getErrorAsArray(GraphQLError error) {
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

    private static synchronized GraphQL getGraphql() {
        if (graphql == null) {
            graphql = GraphQL.newGraphQL(
                    new SchemaGenerator().makeExecutableSchema(
                        new SchemaParser().parse(getSchemaContent()),
                        getRuntimeWiring()
                    )
                )
                .defaultDataFetcherExceptionHandler(getDataFetcherExceptionHandler())
                .build();
        }
        return graphql;
    }

    private static String getSchemaContent() {
        try (InputStream inputStream = GraphqlController.class.getResourceAsStream("schema.graphql")) {
            if (inputStream == null) {
                throw new UncheckedIOException(new IOException("schema.graphql not found"));
            }
            return new String(inputStream.readAllBytes(), StandardCharsets.UTF_8);
        }
        catch (IOException e) {
            throw new UncheckedIOException(e);
        }
    }

    /**
     * Todos los campos pasan por getFieldValue(): el typeConfigDecorator de task-php.
     */
    private static RuntimeWiring getRuntimeWiring() {
        return RuntimeWiring.newRuntimeWiring()
            .wiringFactory(new WiringFactory() {
                @Override
                public boolean providesDataFetcher(FieldWiringEnvironment environment) {
                    return true;
                }

                @Override
                public DataFetcher<?> getDataFetcher(FieldWiringEnvironment environment) {
                    String typeName = environment.getParentType().getName();
                    String fieldName = environment.getFieldDefinition().getName();
                    return dataFetchingEnvironment -> getFieldValue(typeName, fieldName, dataFetchingEnvironment);
                }
            })
            .build();
    }

    /**
     * El resolver del tipo.campo si lo hay; si no, el campo sale del mapa del ResultDto (resolver por defecto).
     */
    private static Object getFieldValue(String typeName, String fieldName, DataFetchingEnvironment dataFetchingEnvironment) {
        Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> resolversByType =
            dataFetchingEnvironment.getGraphQlContext().get(CONTEXT_KEY_RESOLVERS_BY_TYPE);
        Map<String, Object> parent = Primitives.getPrimitives(dataFetchingEnvironment.getSource());

        BiFunction<Map<String, Object>, Map<String, Object>, Object> resolver = resolversByType
            .getOrDefault(typeName, Map.of())
            .get(fieldName);
        return resolver != null
            ? resolver.apply(dataFetchingEnvironment.getArguments(), parent)
            : parent.get(fieldName);
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

    /**
     * Tipo de schema.graphql => campo => fn(args, parent).
     */
    private Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> getResolversByType() {
        Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> queryResolvers = new LinkedHashMap<>();
        queryResolvers.put("users", (args, parent) -> listUsersApiController.invoke());
        queryResolvers.put("user", (args, parent) -> getUserApiController.invoke(args));
        queryResolvers.put("tasks", (args, parent) -> searchTasksApiController.invoke("", args));
        queryResolvers.put("task", (args, parent) -> getTaskApiController.invoke(args));
        queryResolvers.put("userTasks", (args, parent) -> searchTasksApiController.invoke(
            Primitives.getTrimmedString(args.get(TasksKeyEnum.USER_UUID)),
            args
        ));

        Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> mutationResolvers = new LinkedHashMap<>();
        mutationResolvers.put("createUser", (args, parent) -> createUserApiController.invoke(args));
        mutationResolvers.put("logIn", (args, parent) -> logInUserApiController.invoke(args));
        mutationResolvers.put("updateUser", (args, parent) -> updateUserApiController.invoke(args));
        mutationResolvers.put("deleteUser", (args, parent) -> deleteUserApiController.invoke(args));
        mutationResolvers.put("regenerateApiToken", (args, parent) -> regenerateUserApiTokenApiController.invoke(args));
        mutationResolvers.put("createTask", (args, parent) -> createTaskApiController.invoke(args));
        mutationResolvers.put("updateTask", (args, parent) -> updateTaskApiController.invoke(args));
        mutationResolvers.put("deleteTask", (args, parent) -> deleteTaskApiController.invoke(args));

        Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> userResolvers = new LinkedHashMap<>();
        userResolvers.put("tasks", (args, user) -> searchTasksApiController.invoke(
            Primitives.getTrimmedString(user.get(UsersKeyEnum.USER_UUID)),
            args
        ));

        Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> resolversByType = new LinkedHashMap<>();
        resolversByType.put(GraphqlEnum.TYPE_QUERY, queryResolvers);
        resolversByType.put(GraphqlEnum.TYPE_MUTATION, mutationResolvers);
        resolversByType.put(GraphqlEnum.TYPE_USER, userResolvers);
        return resolversByType;
    }

    /**
     * "variables" puede llegar como objeto JSON o, en algunos clientes, como string JSON.
     */
    private Map<String, Object> getVariables() {
        Object variables = httpRequest.getPost(GraphqlEnum.KEY_VARIABLES, Map.of());
        if (variables instanceof String variablesJson) {
            variables = JsonSerializer.getInstance().getDecoded(variablesJson);
        }
        return new LinkedHashMap<>(Primitives.getPrimitives(variables));
    }

    private HttpResponder getGraphqlErrorResponse(int code, String message) {
        return getGraphqlResponse(code, Primitives.of(
            GraphqlEnum.KEY_ERRORS, List.of(Primitives.of(GraphqlEnum.KEY_MESSAGE, message))
        ));
    }

    private HttpResponder getGraphqlResponse(int code, Map<String, Object> graphqlResult) {
        return HttpResponder.json(JsonSerializer.getInstance().getJson(graphqlResult), code);
    }
}
