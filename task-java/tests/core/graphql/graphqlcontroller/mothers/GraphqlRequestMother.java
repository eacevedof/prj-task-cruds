package core.graphql.graphqlcontroller.mothers;

import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.components.HttpRequestMock;
import modules.shared.infrastructure.repositories.TasksRawRepositoryMock;
import modules.shared.infrastructure.repositories.UsersRawRepositoryMock;
import modules.tasks.domain.enums.TaskStatusEnum;

public final class GraphqlRequestMother {

    private static final String MUTATION_CREATE_USER = "mutation($userName: String!, $userEmail: String!, $password: String!) { createUser(user_name: $userName, user_email: $userEmail, password: $password) { user_uuid } }";
    private static final String MUTATION_UPDATE_USER = "mutation($userUuid: ID!, $userName: String!, $userEmail: String!) { updateUser(user_uuid: $userUuid, user_name: $userName, user_email: $userEmail) { user_uuid user_name user_email } }";
    private static final String MUTATION_REGENERATE_API_TOKEN = "mutation($userUuid: ID!) { regenerateApiToken(user_uuid: $userUuid) { user_uuid api_token } }";
    private static final String MUTATION_UPDATE_TASK = "mutation($taskUuid: ID!, $taskTitle: String!, $taskStatus: String, $taskType: String) { updateTask(task_uuid: $taskUuid, task_title: $taskTitle, task_status: $taskStatus, task_type: $taskType) { task_uuid task_title task_status } }";

    public static GraphqlRequestMother getInstance() {
        return new GraphqlRequestMother();
    }

    /**
     * @return argumentos de GraphqlController.execute() (ninguno: todo va en el body)
     */
    public Map<String, String> getRequestByTestCase(String useCase) {
        String userUuidOk = UsersRawRepositoryMock.USER_UUID_OK;
        return switch (useCase) {
            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_USERS_QUERY ->
                getGraphqlRequest("{ users { user_uuid user_name user_email } }");

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_USER_WITH_TASKS_QUERY ->
                getGraphqlRequest("{ user(user_uuid: \"" + userUuidOk + "\") { user_uuid tasks { items { task_uuid task_title } total_items } } }");

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_WITHOUT_FILTERS_NEWEST_FIRST ->
                getSearchRequest("{ tasks { items { task_id task_title } page page_size total_items total_pages } }");

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_SECOND_PAGE ->
                getSearchRequest("{ tasks(page: 2, page_size: 2) { items { task_title } page total_items total_pages } }");

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TEXT_AND_STATUS -> getSearchRequest(
                "query($filter: TasksFilter) { tasks(filter: $filter) { items { task_title } total_items } }",
                Primitives.of("filter", Primitives.of("task_text", "SEARCH", "task_status", TaskStatusEnum.PENDING))
            );

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TARGET_DATE_RANGE -> getSearchRequest(
                "query($filter: TasksFilter) { tasks(filter: $filter) { items { task_title } total_items } }",
                Primitives.of("filter", Primitives.of("target_date_from", "2026-01-01", "target_date_to", "2026-04-01"))
            );

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_TEXT_WITH_LIKE_WILDCARD ->
                getSearchRequest("{ tasks(filter: {task_text: \"%\"}) { items { task_title } total_items } }");

            case GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_PAGINATION ->
                getSearchRequest("{ tasks(page: 0, page_size: 101) { total_items } }");

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_TASK_QUERY_WITH_VARIABLES -> getGraphqlRequest(
                "query GetTask($taskUuid: ID!) { task(task_uuid: $taskUuid) { task_uuid task_title } }",
                Primitives.of("taskUuid", TasksRawRepositoryMock.TASK_UUID_OK)
            );

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_CREATE_USER_MUTATION -> getGraphqlRequest(
                "mutation($userName: String!, $userEmail: String!, $password: String!) { createUser(user_name: $userName, user_email: $userEmail, password: $password) { user_uuid user_email } }",
                Primitives.of(
                    "userName", UsersRawRepositoryMock.USER_NAME_NEW_GRAPHQL,
                    "userEmail", UsersRawRepositoryMock.USER_EMAIL_NEW_GRAPHQL,
                    "password", UsersRawRepositoryMock.USER_PASSWORD_NEW
                )
            );

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_CREATE_TASK_MUTATION ->
                getGraphqlRequest("mutation { createTask(task_title: \"Task GraphQL\") { task_uuid user_uuid task_status task_type } }");

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_DELETE_TASK_MUTATION -> getGraphqlRequest(
                "mutation($taskUuid: ID!) { deleteTask(task_uuid: $taskUuid) { task_uuid affected } }",
                Primitives.of("taskUuid", TasksRawRepositoryMock.TASK_UUID_TO_DELETE_GRAPHQL)
            );

            case GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID -> getGraphqlRequest(
                "query($userUuid: ID!) { user(user_uuid: $userUuid) { user_uuid } }",
                Primitives.of("userUuid", UsersRawRepositoryMock.USER_UUID_NOK)
            );

            case GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_EMAIL_MUTATION -> getGraphqlRequest(
                MUTATION_CREATE_USER,
                Primitives.of(
                    "userName", UsersRawRepositoryMock.USER_NAME_OTHER,
                    "userEmail", UsersRawRepositoryMock.USER_EMAIL_INVALID,
                    "password", UsersRawRepositoryMock.USER_PASSWORD_NEW
                )
            );

            case GraphqlTestCaseEnum.NOK_AS_CONFLICT_BY_EXISTING_EMAIL_MUTATION -> getGraphqlRequest(
                MUTATION_CREATE_USER,
                Primitives.of(
                    "userName", UsersRawRepositoryMock.USER_NAME_OTHER,
                    "userEmail", UsersRawRepositoryMock.USER_EMAIL_OK,
                    "password", UsersRawRepositoryMock.USER_PASSWORD_NEW
                )
            );

            case GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_TASK_STATUS_MUTATION -> getGraphqlRequest(
                "mutation($taskStatus: String) { createTask(task_title: \"Task GraphQL\", task_status: $taskStatus) { task_uuid } }",
                Primitives.of("taskStatus", TasksRawRepositoryMock.TASK_STATUS_INVALID)
            );

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_CREATE_USER_MUTATION_WITHOUT_TOKEN -> getGraphqlRequest(
                "mutation($userName: String!, $userEmail: String!, $password: String!) { createUser(user_name: $userName, user_email: $userEmail, password: $password) { user_uuid api_token } }",
                Primitives.of(
                    "userName", UsersRawRepositoryMock.USER_NAME_NEW_GRAPHQL_WITHOUT_TOKEN,
                    "userEmail", UsersRawRepositoryMock.USER_EMAIL_NEW_GRAPHQL_WITHOUT_TOKEN,
                    "password", UsersRawRepositoryMock.USER_PASSWORD_NEW
                ),
                ""
            );

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_LOG_IN_MUTATION_WITHOUT_TOKEN -> getGraphqlRequest(
                "mutation($userName: String!, $password: String!) { logIn(user_name: $userName, password: $password) { user_uuid api_token } }",
                Primitives.of(
                    "userName", UsersRawRepositoryMock.USER_NAME_TO_LOG_IN,
                    "password", UsersRawRepositoryMock.USER_PASSWORD_TO_LOG_IN
                ),
                ""
            );

            case GraphqlTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD_LOG_IN_MUTATION -> getGraphqlRequest(
                "mutation($userName: String!, $password: String!) { logIn(user_name: $userName, password: $password) { user_uuid } }",
                Primitives.of(
                    "userName", UsersRawRepositoryMock.USER_NAME_TO_LOG_IN,
                    "password", UsersRawRepositoryMock.USER_PASSWORD_NOK
                ),
                ""
            );

            case GraphqlTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN ->
                getGraphqlRequest("{ users { user_uuid } }", Primitives.of(), "");

            case GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER -> getGraphqlRequest(
                "query($taskUuid: ID!) { task(task_uuid: $taskUuid) { task_uuid } }",
                Primitives.of("taskUuid", TasksRawRepositoryMock.TASK_UUID_OK),
                UsersRawRepositoryMock.USER_API_TOKEN_TO_UPDATE
            );

            case GraphqlTestCaseEnum.NOK_AS_GRAPHQL_ERROR_BY_UNKNOWN_FIELD ->
                getGraphqlRequest("{ users { unknown_field } }");

            case GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_EMPTY_QUERY ->
                getGraphqlRequest("   ");

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_UPDATE_USER_MUTATION -> getGraphqlRequest(
                MUTATION_UPDATE_USER,
                Primitives.of(
                    "userUuid", UsersRawRepositoryMock.USER_UUID_TO_UPDATE,
                    "userName", UsersRawRepositoryMock.USER_NAME_UPDATED,
                    "userEmail", UsersRawRepositoryMock.USER_EMAIL_UPDATED
                ),
                UsersRawRepositoryMock.USER_API_TOKEN_TO_UPDATE
            );

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_DELETE_USER_MUTATION -> getGraphqlRequest(
                "mutation($userUuid: ID!) { deleteUser(user_uuid: $userUuid) { user_uuid affected } }",
                Primitives.of("userUuid", UsersRawRepositoryMock.USER_UUID_TO_DELETE),
                UsersRawRepositoryMock.USER_API_TOKEN_TO_DELETE
            );

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_REGENERATE_API_TOKEN_MUTATION -> getGraphqlRequest(
                MUTATION_REGENERATE_API_TOKEN,
                Primitives.of("userUuid", UsersRawRepositoryMock.USER_UUID_TO_REGENERATE),
                UsersRawRepositoryMock.USER_API_TOKEN_TO_REGENERATE
            );

            case GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_UPDATE_TASK_MUTATION -> getGraphqlRequest(
                MUTATION_UPDATE_TASK,
                Primitives.of(
                    "taskUuid", TasksRawRepositoryMock.TASK_UUID_TO_UPDATE,
                    "taskTitle", "Task actualizada",
                    "taskStatus", TaskStatusEnum.DONE
                )
            );

            case GraphqlTestCaseEnum.NOK_AS_CONFLICT_BY_EXISTING_USER_NAME_MUTATION -> getGraphqlRequest(
                MUTATION_CREATE_USER,
                Primitives.of(
                    "userName", UsersRawRepositoryMock.USER_NAME_OK,
                    "userEmail", UsersRawRepositoryMock.USER_EMAIL_NEW,
                    "password", UsersRawRepositoryMock.USER_PASSWORD_NEW
                ),
                ""
            );

            case GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_USER_NAME_AND_SHORT_PASSWORD_MUTATION -> getGraphqlRequest(
                MUTATION_CREATE_USER,
                Primitives.of(
                    "userName", UsersRawRepositoryMock.USER_NAME_INVALID,
                    "userEmail", UsersRawRepositoryMock.USER_EMAIL_NEW,
                    "password", "short"
                ),
                ""
            );

            case GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_EMPTY_USER_NAME_UPDATE_USER_MUTATION -> getGraphqlRequest(
                MUTATION_UPDATE_USER,
                Primitives.of(
                    "userUuid", UsersRawRepositoryMock.USER_UUID_TO_UPDATE,
                    "userName", "  ",
                    "userEmail", UsersRawRepositoryMock.USER_EMAIL_UPDATED
                ),
                UsersRawRepositoryMock.USER_API_TOKEN_TO_UPDATE
            );

            case GraphqlTestCaseEnum.NOK_AS_CONFLICT_BY_EMAIL_OF_OTHER_USER_UPDATE_USER_MUTATION -> getGraphqlRequest(
                MUTATION_UPDATE_USER,
                Primitives.of(
                    "userUuid", UsersRawRepositoryMock.USER_UUID_TO_UPDATE,
                    "userName", UsersRawRepositoryMock.USER_NAME_UPDATED,
                    "userEmail", UsersRawRepositoryMock.USER_EMAIL_OK
                ),
                UsersRawRepositoryMock.USER_API_TOKEN_TO_UPDATE
            );

            case GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_UPDATE_USER_MUTATION -> getGraphqlRequest(
                MUTATION_UPDATE_USER,
                Primitives.of(
                    "userUuid", UsersRawRepositoryMock.USER_UUID_TO_UPDATE,
                    "userName", UsersRawRepositoryMock.USER_NAME_OTHER,
                    "userEmail", UsersRawRepositoryMock.USER_EMAIL_NEW
                )
            );

            case GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_REGENERATE_API_TOKEN_MUTATION -> getGraphqlRequest(
                MUTATION_REGENERATE_API_TOKEN,
                Primitives.of("userUuid", UsersRawRepositoryMock.USER_UUID_TO_REGENERATE)
            );

            case GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID -> getGraphqlRequest(
                "query($userUuid: ID!) { user(user_uuid: $userUuid) { user_uuid } }",
                Primitives.of("userUuid", UsersRawRepositoryMock.USER_UUID_TO_UPDATE)
            );

            case GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_USER_TASKS_QUERY -> getGraphqlRequest(
                "query($userUuid: ID!) { userTasks(user_uuid: $userUuid) { total_items } }",
                Primitives.of("userUuid", UsersRawRepositoryMock.USER_UUID_TO_UPDATE)
            );

            case GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID -> getGraphqlRequest(
                "query($taskUuid: ID!) { task(task_uuid: $taskUuid) { task_uuid } }",
                Primitives.of("taskUuid", TasksRawRepositoryMock.TASK_UUID_NOK)
            );

            case GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_TARGET_DATE_MUTATION -> getGraphqlRequest(
                "mutation($targetDate: String) { createTask(task_title: \"Task GraphQL\", target_date: $targetDate) { task_uuid } }",
                Primitives.of("targetDate", TasksRawRepositoryMock.TARGET_DATE_INVALID)
            );

            case GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_TASK_TYPE_UPDATE_TASK_MUTATION -> getGraphqlRequest(
                MUTATION_UPDATE_TASK,
                Primitives.of(
                    "taskUuid", TasksRawRepositoryMock.TASK_UUID_TO_UPDATE,
                    "taskTitle", "Task actualizada",
                    "taskType", TasksRawRepositoryMock.TASK_TYPE_INVALID
                )
            );

            case GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER_UPDATE_TASK_MUTATION -> getGraphqlRequest(
                MUTATION_UPDATE_TASK,
                Primitives.of(
                    "taskUuid", TasksRawRepositoryMock.TASK_UUID_TO_UPDATE,
                    "taskTitle", "Task de otro"
                ),
                UsersRawRepositoryMock.USER_API_TOKEN_TO_UPDATE
            );

            case GraphqlTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_INVALID_TOKEN ->
                getGraphqlRequest("{ users { user_uuid } }", Primitives.of(), UsersRawRepositoryMock.USER_API_TOKEN_NOK);

            default -> throw new IllegalArgumentException("unknown test case " + useCase);
        };
    }

    private Map<String, String> getSearchRequest(String query) {
        return getSearchRequest(query, Primitives.of());
    }

    /**
     * Las busquedas van con el dueño de las tasks de busqueda: sus totales no cambian por otros tests.
     */
    private Map<String, String> getSearchRequest(String query, Map<String, Object> variables) {
        return getGraphqlRequest(query, variables, UsersRawRepositoryMock.USER_API_TOKEN_SEARCH);
    }

    private Map<String, String> getGraphqlRequest(String query) {
        return getGraphqlRequest(query, Primitives.of());
    }

    private Map<String, String> getGraphqlRequest(String query, Map<String, Object> variables) {
        return getGraphqlRequest(query, variables, UsersRawRepositoryMock.USER_API_TOKEN_OK);
    }

    /**
     * Por defecto con el token del usuario OK; "" para probar sin autenticar.
     */
    private Map<String, String> getGraphqlRequest(String query, Map<String, Object> variables, String bearerToken) {
        return HttpRequestMock.getInstance()
            .setBearerToken(bearerToken)
            .setPostRawParams(Primitives.of("query", query, "variables", variables))
            .getMockedInstance();
    }
}
