package core.graphql.graphqlcontroller;

import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;
import java.util.Map;

import org.junit.jupiter.api.BeforeAll;
import org.junit.jupiter.api.Test;

import core.graphql.graphqlcontroller.mothers.GraphqlControllerMother;
import core.graphql.graphqlcontroller.mothers.GraphqlRequestMother;
import core.graphql.graphqlcontroller.mothers.GraphqlTestCaseEnum;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;
import modules.shared.infrastructure.repositories.GraphqlResponseRepositoryMock;
import modules.shared.infrastructure.repositories.TasksRawRepositoryMock;
import modules.shared.infrastructure.repositories.UsersRawRepositoryMock;
import modules.tasks.domain.entities.TaskEntity;
import modules.tasks.domain.enums.TaskStatusEnum;
import modules.tasks.domain.enums.TaskTypeEnum;

import testcase.AbstractTestCase;
import testcase.Bootstrap;

/**
 * GraphQL responde 200 tambien con errores de dominio: el codigo HTTP equivalente va en errors[].extensions.code
 */
class GraphqlControllerTest extends AbstractTestCase {

    @BeforeAll
    static void setUpTestDb() {
        Bootstrap.runWithTestDb("graphql");
    }

    @Test
    void testOkAsSuccessByUsersQuery() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_USERS_QUERY);

        assertEmpty(graphqlResponseRepositoryMock.getErrors());
        assertContains(
            UsersRawRepositoryMock.USER_UUID_OK,
            getFieldValues(Primitives.getPrimitivesList(graphqlResponseRepositoryMock.getData().get("users")), "user_uuid")
        );
    }

    @Test
    void testOkAsSuccessByUserWithTasksQuery() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_USER_WITH_TASKS_QUERY);

        assertEmpty(graphqlResponseRepositoryMock.getErrors());
        Map<String, Object> user = Primitives.getPrimitives(graphqlResponseRepositoryMock.getData().get("user"));
        Map<String, Object> taskPage = Primitives.getPrimitives(user.get("tasks"));
        assertContains(
            TasksRawRepositoryMock.TASK_UUID_OK,
            getFieldValues(Primitives.getPrimitivesList(taskPage.get("items")), "task_uuid")
        );
    }

    @Test
    void testOkAsSuccessBySearchWithoutFiltersNewestFirst() {
        Map<String, Object> taskPage = getTaskPage(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_WITHOUT_FILTERS_NEWEST_FIRST);

        assertEquals(
            List.of(TasksRawRepositoryMock.SEARCH_TITLE_GAMMA, TasksRawRepositoryMock.SEARCH_TITLE_BETA, TasksRawRepositoryMock.SEARCH_TITLE_ALPHA),
            getTaskTitles(taskPage)
        );
        List<Integer> taskIds = new ArrayList<>();
        for (Object taskId : getFieldValues(Primitives.getPrimitivesList(taskPage.get("items")), "task_id")) {
            taskIds.add(Primitives.getInt(taskId));
        }
        List<Integer> taskIdsDesc = new ArrayList<>(taskIds);
        taskIdsDesc.sort(Comparator.reverseOrder());
        assertSame(taskIdsDesc, taskIds);
        assertEquals(List.of(1, 20, 3, 1), getInts(taskPage, "page", "page_size", "total_items", "total_pages"));
    }

    @Test
    void testOkAsSuccessBySearchSecondPage() {
        Map<String, Object> taskPage = getTaskPage(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_SECOND_PAGE);

        assertEquals(List.of(TasksRawRepositoryMock.SEARCH_TITLE_ALPHA), getTaskTitles(taskPage));
        assertEquals(List.of(2, 3, 2), getInts(taskPage, "page", "total_items", "total_pages"));
    }

    @Test
    void testOkAsSuccessBySearchFilteredByTextAndStatus() {
        Map<String, Object> taskPage = getTaskPage(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TEXT_AND_STATUS);

        assertEquals(
            List.of(TasksRawRepositoryMock.SEARCH_TITLE_GAMMA, TasksRawRepositoryMock.SEARCH_TITLE_ALPHA),
            getTaskTitles(taskPage)
        );
        assertEquals(2, Primitives.getInt(taskPage.get("total_items")));
    }

    @Test
    void testOkAsSuccessBySearchFilteredByTargetDateRange() {
        Map<String, Object> taskPage = getTaskPage(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TARGET_DATE_RANGE);

        assertEquals(List.of(TasksRawRepositoryMock.SEARCH_TITLE_ALPHA), getTaskTitles(taskPage));
    }

    @Test
    void testOkAsSuccessBySearchTextWithLikeWildcard() {
        Map<String, Object> taskPage = getTaskPage(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_TEXT_WITH_LIKE_WILDCARD);

        // "%" se busca literal: solo la descripcion que lo contiene, no todas
        assertEquals(List.of(TasksRawRepositoryMock.SEARCH_TITLE_GAMMA), getTaskTitles(taskPage));
    }

    @Test
    void testNokAsBadRequestByInvalidPagination() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_PAGINATION);

        assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode());
        // los dos campos erroneos viajan en el mensaje de la FieldsException
        List<String> errorDetails = graphqlResponseRepositoryMock.getFirstErrorDetails();
        assertCount(1, errorDetails);
        assertStringContainsString("page:", errorDetails.getFirst());
        assertStringContainsString("page_size:", errorDetails.getFirst());
    }

    @Test
    void testOkAsSuccessByTaskQueryWithVariables() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_TASK_QUERY_WITH_VARIABLES);

        assertEmpty(graphqlResponseRepositoryMock.getErrors());
        assertEquals(TasksRawRepositoryMock.TASK_TITLE_OK, getDataField(graphqlResponseRepositoryMock, "task").get("task_title"));
    }

    @Test
    void testOkAsSuccessByCreateUserMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_CREATE_USER_MUTATION);

        assertEmpty(graphqlResponseRepositoryMock.getErrors());
        assertEquals(
            UsersRawRepositoryMock.USER_EMAIL_NEW_GRAPHQL,
            getDataField(graphqlResponseRepositoryMock, "createUser").get("user_email")
        );
    }

    @Test
    void testOkAsSuccessByCreateTaskMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_CREATE_TASK_MUTATION);

        assertEmpty(graphqlResponseRepositoryMock.getErrors());
        Map<String, Object> createdTask = getDataField(graphqlResponseRepositoryMock, "createTask");
        assertEquals(UsersRawRepositoryMock.USER_UUID_OK, createdTask.get("user_uuid"));
        assertStringStartsWith(TaskEntity.TASK_UUID_PREFIX, Primitives.getTrimmedString(createdTask.get("task_uuid")));
        assertEquals(TaskStatusEnum.DEFAULT, createdTask.get("task_status"));
        assertEquals(TaskTypeEnum.DEFAULT, createdTask.get("task_type"));
    }

    @Test
    void testOkAsSuccessByDeleteTaskMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_DELETE_TASK_MUTATION);

        assertEmpty(graphqlResponseRepositoryMock.getErrors());
        assertEquals(1, Primitives.getInt(getDataField(graphqlResponseRepositoryMock, "deleteTask").get("affected")));
    }

    @Test
    void testOkAsSuccessByCreateUserMutationWithoutToken() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_CREATE_USER_MUTATION_WITHOUT_TOKEN);

        assertEmpty(graphqlResponseRepositoryMock.getErrors());
        assertNotEmpty(getDataField(graphqlResponseRepositoryMock, "createUser").get("api_token"));
    }

    @Test
    void testOkAsSuccessByLogInMutationWithoutToken() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_LOG_IN_MUTATION_WITHOUT_TOKEN);

        assertEmpty(graphqlResponseRepositoryMock.getErrors());
        Map<String, Object> loggedInUser = getDataField(graphqlResponseRepositoryMock, "logIn");
        assertEquals(UsersRawRepositoryMock.USER_UUID_TO_LOG_IN, loggedInUser.get("user_uuid"));
        // token nuevo: el de los fixtures deja de valer
        assertNotEquals(UsersRawRepositoryMock.USER_API_TOKEN_TO_LOG_IN, loggedInUser.get("api_token"));
    }

    @Test
    void testNokAsUnauthorizedByWrongPasswordLogInMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD_LOG_IN_MUTATION);

        assertEquals(ExceptionEnum.CODE_UNAUTHORIZED, graphqlResponseRepositoryMock.getFirstErrorCode());
        assertNull(graphqlResponseRepositoryMock.getData().get("logIn"));
    }

    @Test
    void testNokAsUnauthorizedByMissingToken() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN);

        assertEquals(ExceptionEnum.CODE_UNAUTHORIZED, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    @Test
    void testNokAsNotFoundByTaskOfOtherUser() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER);

        assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
        assertNull(graphqlResponseRepositoryMock.getData().get("task"));
    }

    @Test
    void testNokAsNotFoundByFakeUserUuid() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID);

        assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
        assertNull(graphqlResponseRepositoryMock.getData().get("user"));
    }

    @Test
    void testNokAsBadRequestByInvalidEmailMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_EMAIL_MUTATION);

        assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode());
        assertNotEmpty(graphqlResponseRepositoryMock.getFirstErrorDetails());
    }

    @Test
    void testNokAsConflictByExistingEmailMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_CONFLICT_BY_EXISTING_EMAIL_MUTATION);

        assertEquals(ExceptionEnum.CODE_CONFLICT, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    @Test
    void testNokAsBadRequestByInvalidTaskStatusMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_TASK_STATUS_MUTATION);

        assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    @Test
    void testNokAsGraphqlErrorByUnknownField() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_GRAPHQL_ERROR_BY_UNKNOWN_FIELD);

        assertNotEmpty(graphqlResponseRepositoryMock.getErrors());
        assertEquals(0, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    @Test
    void testOkAsSuccessByUpdateUserMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_UPDATE_USER_MUTATION);

        assertEmpty(graphqlResponseRepositoryMock.getErrors());
        Map<String, Object> updatedUser = getDataField(graphqlResponseRepositoryMock, "updateUser");
        assertEquals(UsersRawRepositoryMock.USER_NAME_UPDATED, updatedUser.get("user_name"));
        assertEquals(UsersRawRepositoryMock.USER_EMAIL_UPDATED, updatedUser.get("user_email"));
    }

    @Test
    void testOkAsSuccessByDeleteUserMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_DELETE_USER_MUTATION);

        assertEmpty(graphqlResponseRepositoryMock.getErrors());
        assertEquals(1, Primitives.getInt(getDataField(graphqlResponseRepositoryMock, "deleteUser").get("affected")));
    }

    @Test
    void testOkAsSuccessByRegenerateApiTokenMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_REGENERATE_API_TOKEN_MUTATION);

        assertEmpty(graphqlResponseRepositoryMock.getErrors());
        Object newApiToken = getDataField(graphqlResponseRepositoryMock, "regenerateApiToken").get("api_token");
        assertNotEmpty(newApiToken);
        assertNotEquals(UsersRawRepositoryMock.USER_API_TOKEN_TO_REGENERATE, newApiToken);
    }

    @Test
    void testOkAsSuccessByUpdateTaskMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_UPDATE_TASK_MUTATION);

        assertEmpty(graphqlResponseRepositoryMock.getErrors());
        assertEquals(TaskStatusEnum.DONE, getDataField(graphqlResponseRepositoryMock, "updateTask").get("task_status"));
    }

    @Test
    void testNokAsConflictByExistingUserNameMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_CONFLICT_BY_EXISTING_USER_NAME_MUTATION);

        assertEquals(ExceptionEnum.CODE_CONFLICT, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    @Test
    void testNokAsBadRequestByInvalidUserNameAndShortPasswordMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_USER_NAME_AND_SHORT_PASSWORD_MUTATION);

        assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode());
        String errorDetails = String.join(" ", graphqlResponseRepositoryMock.getFirstErrorDetails());
        assertStringContainsString("user_name:", errorDetails);
        assertStringContainsString("password:", errorDetails);
    }

    @Test
    void testNokAsBadRequestByEmptyUserNameUpdateUserMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_EMPTY_USER_NAME_UPDATE_USER_MUTATION);

        assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    @Test
    void testNokAsConflictByEmailOfOtherUserUpdateUserMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_CONFLICT_BY_EMAIL_OF_OTHER_USER_UPDATE_USER_MUTATION);

        assertEquals(ExceptionEnum.CODE_CONFLICT, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    @Test
    void testNokAsNotFoundByOtherUserUuidUpdateUserMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_UPDATE_USER_MUTATION);

        assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
        assertNull(graphqlResponseRepositoryMock.getData().get("updateUser"));
    }

    @Test
    void testNokAsNotFoundByOtherUserUuidRegenerateApiTokenMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_REGENERATE_API_TOKEN_MUTATION);

        assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    @Test
    void testNokAsNotFoundByOtherUserUuid() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID);

        assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
        assertNull(graphqlResponseRepositoryMock.getData().get("user"));
    }

    @Test
    void testNokAsNotFoundByOtherUserUuidUserTasksQuery() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_USER_TASKS_QUERY);

        assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    @Test
    void testNokAsNotFoundByFakeTaskUuid() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID);

        assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
        assertNull(graphqlResponseRepositoryMock.getData().get("task"));
    }

    @Test
    void testNokAsBadRequestByInvalidTargetDateMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_TARGET_DATE_MUTATION);

        assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    @Test
    void testNokAsBadRequestByInvalidTaskTypeUpdateTaskMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_TASK_TYPE_UPDATE_TASK_MUTATION);

        assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    @Test
    void testNokAsNotFoundByTaskOfOtherUserUpdateTaskMutation() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER_UPDATE_TASK_MUTATION);

        assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
        assertNull(graphqlResponseRepositoryMock.getData().get("updateTask"));
    }

    @Test
    void testNokAsUnauthorizedByInvalidToken() {
        GraphqlResponseRepositoryMock graphqlResponseRepositoryMock = getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_INVALID_TOKEN);

        assertEquals(ExceptionEnum.CODE_UNAUTHORIZED, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    @Test
    void testNokAsBadRequestByEmptyQuery() {
        GraphqlRequestMother.getInstance().getRequestByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_EMPTY_QUERY);

        HttpResponder httpResponse = GraphqlControllerMother.getControllerInstance().execute();

        assertInstanceOf(HttpResponder.class, httpResponse);
        assertEquals(ExceptionEnum.CODE_BAD_REQUEST, httpResponse.getStatusCode());
    }

    /**
     * Toda peticion GraphQL que se ejecuta responde 200; lo que cambia es data / errors.
     */
    private GraphqlResponseRepositoryMock getGraphqlResponseByTestCase(String graphqlTestCaseEnum) {
        GraphqlRequestMother.getInstance().getRequestByTestCase(graphqlTestCaseEnum);

        HttpResponder httpResponse = GraphqlControllerMother.getControllerInstance().execute();

        assertInstanceOf(HttpResponder.class, httpResponse);
        assertEquals(ExceptionEnum.CODE_OK, httpResponse.getStatusCode());
        return GraphqlResponseRepositoryMock.fromHttpResponse(httpResponse);
    }

    /**
     * data.tasks de una busqueda (TaskPage).
     */
    private Map<String, Object> getTaskPage(String graphqlTestCaseEnum) {
        return getDataField(getGraphqlResponseByTestCase(graphqlTestCaseEnum), "tasks");
    }

    private Map<String, Object> getDataField(GraphqlResponseRepositoryMock graphqlResponseRepositoryMock, String field) {
        return Primitives.getPrimitives(graphqlResponseRepositoryMock.getData().get(field));
    }

    private List<Object> getTaskTitles(Map<String, Object> taskPage) {
        return getFieldValues(Primitives.getPrimitivesList(taskPage.get("items")), "task_title");
    }

    private List<Object> getFieldValues(List<Map<String, Object>> items, String field) {
        List<Object> fieldValues = new ArrayList<>();
        for (Map<String, Object> item : items) {
            fieldValues.add(item.get(field));
        }
        return fieldValues;
    }

    /**
     * Los numeros de un JSON decodificado llegan como Long: aqui como int para compararlos con literales.
     */
    private List<Integer> getInts(Map<String, Object> primitives, String... keys) {
        List<Integer> ints = new ArrayList<>();
        for (String key : keys) {
            ints.add(Primitives.getInt(primitives.get(key)));
        }
        return ints;
    }
}
