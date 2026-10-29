import { runWithTestDb } from "#tests/bootstrap.ts";

import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";
import { TaskEntity } from "#app/Modules/Tasks/Domain/Entities/TaskEntity.ts";
import { TaskStatusEnum } from "#app/Modules/Tasks/Domain/Enums/TaskStatusEnum.ts";
import { TaskTypeEnum } from "#app/Modules/Tasks/Domain/Enums/TaskTypeEnum.ts";
import { AbstractTestCase } from "#tests/AbstractTestCase.ts";
import { GraphqlControllerMother } from "#tests/Modules/Graphql/Infrastructure/Controllers/GraphqlController/Mothers/GraphqlControllerMother.ts";
import { GraphqlRequestMother } from "#tests/Modules/Graphql/Infrastructure/Controllers/GraphqlController/Mothers/GraphqlRequestMother.ts";
import { GraphqlTestCaseEnum, type GraphqlTestCase } from "#tests/Modules/Graphql/Infrastructure/Controllers/GraphqlController/Mothers/GraphqlTestCaseEnum.ts";
import { GraphqlResponseRepositoryMock } from "#tests/Modules/Graphql/Infrastructure/Repositories/GraphqlResponseRepositoryMock.ts";
import { TasksRawRepositoryMock } from "#tests/Modules/Shared/Infrastructure/Repositories/TasksRawRepositoryMock.ts";
import { UsersRawRepositoryMock } from "#tests/Modules/Shared/Infrastructure/Repositories/UsersRawRepositoryMock.ts";

runWithTestDb("graphql");

/**
 * GraphQL responde 200 tambien con errores de dominio: el codigo HTTP equivalente va en errors[].extensions.code
 */
class GraphqlControllerTest extends AbstractTestCase {

    testOkAsSuccessByUsersQuery(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_USERS_QUERY);

        this.assertEmpty(graphqlResponseRepositoryMock.getErrors());
        this.assertContains(
            UsersRawRepositoryMock.USER_UUID_OK,
            graphqlResponseRepositoryMock.getData().users.map((user: Record<string, unknown>) => user.user_uuid)
        );
    }

    testOkAsSuccessByUserWithTasksQuery(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_USER_WITH_TASKS_QUERY);

        this.assertEmpty(graphqlResponseRepositoryMock.getErrors());
        this.assertContains(
            TasksRawRepositoryMock.TASK_UUID_OK,
            graphqlResponseRepositoryMock.getData().user.tasks.items.map((task: Record<string, unknown>) => task.task_uuid)
        );
    }

    testOkAsSuccessBySearchWithoutFiltersNewestFirst(): void {
        const taskPage = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_WITHOUT_FILTERS_NEWEST_FIRST)
            .getData().tasks;

        this.assertEquals(
            [TasksRawRepositoryMock.SEARCH_TITLE_GAMMA, TasksRawRepositoryMock.SEARCH_TITLE_BETA, TasksRawRepositoryMock.SEARCH_TITLE_ALPHA],
            taskPage.items.map((task: Record<string, unknown>) => task.task_title)
        );
        const taskIds: number[] = taskPage.items.map((task: Record<string, unknown>) => task.task_id);
        const taskIdsDesc = [...taskIds].sort((a, b) => b - a);
        this.assertSame(taskIdsDesc, taskIds);
        this.assertEquals([1, 20, 3, 1], [taskPage.page, taskPage.page_size, taskPage.total_items, taskPage.total_pages]);
    }

    testOkAsSuccessBySearchSecondPage(): void {
        const taskPage = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_SECOND_PAGE)
            .getData().tasks;

        this.assertEquals([TasksRawRepositoryMock.SEARCH_TITLE_ALPHA], taskPage.items.map((task: Record<string, unknown>) => task.task_title));
        this.assertEquals([2, 3, 2], [taskPage.page, taskPage.total_items, taskPage.total_pages]);
    }

    testOkAsSuccessBySearchFilteredByTextAndStatus(): void {
        const taskPage = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TEXT_AND_STATUS)
            .getData().tasks;

        this.assertEquals(
            [TasksRawRepositoryMock.SEARCH_TITLE_GAMMA, TasksRawRepositoryMock.SEARCH_TITLE_ALPHA],
            taskPage.items.map((task: Record<string, unknown>) => task.task_title)
        );
        this.assertEquals(2, taskPage.total_items);
    }

    testOkAsSuccessBySearchFilteredByTargetDateRange(): void {
        const taskPage = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TARGET_DATE_RANGE)
            .getData().tasks;

        this.assertEquals([TasksRawRepositoryMock.SEARCH_TITLE_ALPHA], taskPage.items.map((task: Record<string, unknown>) => task.task_title));
    }

    testOkAsSuccessBySearchTextWithLikeWildcard(): void {
        const taskPage = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_TEXT_WITH_LIKE_WILDCARD)
            .getData().tasks;

        // "%" se busca literal: solo la descripcion que lo contiene, no todas
        this.assertEquals([TasksRawRepositoryMock.SEARCH_TITLE_GAMMA], taskPage.items.map((task: Record<string, unknown>) => task.task_title));
    }

    testNokAsBadRequestByInvalidPagination(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_PAGINATION);

        this.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode());
        // los dos campos erroneos viajan en el mensaje de la FieldsException
        const errorDetails = graphqlResponseRepositoryMock.getFirstErrorDetails();
        this.assertCount(1, errorDetails);
        this.assertStringContainsString("page:", errorDetails[0]);
        this.assertStringContainsString("page_size:", errorDetails[0]);
    }

    testOkAsSuccessByTaskQueryWithVariables(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_TASK_QUERY_WITH_VARIABLES);

        this.assertEmpty(graphqlResponseRepositoryMock.getErrors());
        this.assertEquals(TasksRawRepositoryMock.TASK_TITLE_OK, graphqlResponseRepositoryMock.getData().task.task_title);
    }

    testOkAsSuccessByCreateUserMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_CREATE_USER_MUTATION);

        this.assertEmpty(graphqlResponseRepositoryMock.getErrors());
        this.assertEquals(
            UsersRawRepositoryMock.USER_EMAIL_NEW_GRAPHQL,
            graphqlResponseRepositoryMock.getData().createUser.user_email
        );
    }

    testOkAsSuccessByCreateTaskMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_CREATE_TASK_MUTATION);

        this.assertEmpty(graphqlResponseRepositoryMock.getErrors());
        const createdTask = graphqlResponseRepositoryMock.getData().createTask;
        this.assertEquals(UsersRawRepositoryMock.USER_UUID_OK, createdTask.user_uuid);
        this.assertStringStartsWith(TaskEntity.TASK_UUID_PREFIX, createdTask.task_uuid);
        this.assertEquals(TaskStatusEnum.DEFAULT, createdTask.task_status);
        this.assertEquals(TaskTypeEnum.DEFAULT, createdTask.task_type);
    }

    testOkAsSuccessByDeleteTaskMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_DELETE_TASK_MUTATION);

        this.assertEmpty(graphqlResponseRepositoryMock.getErrors());
        this.assertEquals(1, graphqlResponseRepositoryMock.getData().deleteTask.affected);
    }

    testOkAsSuccessByCreateUserMutationWithoutToken(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_CREATE_USER_MUTATION_WITHOUT_TOKEN);

        this.assertEmpty(graphqlResponseRepositoryMock.getErrors());
        this.assertNotEmpty(graphqlResponseRepositoryMock.getData().createUser.api_token);
    }

    testOkAsSuccessByLogInMutationWithoutToken(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_LOG_IN_MUTATION_WITHOUT_TOKEN);

        this.assertEmpty(graphqlResponseRepositoryMock.getErrors());
        const loggedInUser = graphqlResponseRepositoryMock.getData().logIn;
        this.assertEquals(UsersRawRepositoryMock.USER_UUID_TO_LOG_IN, loggedInUser.user_uuid);
        // token nuevo: el de los fixtures deja de valer
        this.assertNotEquals(UsersRawRepositoryMock.USER_API_TOKEN_TO_LOG_IN, loggedInUser.api_token);
    }

    testNokAsUnauthorizedByWrongPasswordLogInMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD_LOG_IN_MUTATION);

        this.assertEquals(ExceptionEnum.CODE_UNAUTHORIZED, graphqlResponseRepositoryMock.getFirstErrorCode());
        this.assertNull(graphqlResponseRepositoryMock.getData().logIn);
    }

    testNokAsUnauthorizedByMissingToken(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN);

        this.assertEquals(ExceptionEnum.CODE_UNAUTHORIZED, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    testNokAsNotFoundByTaskOfOtherUser(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER);

        this.assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
        this.assertNull(graphqlResponseRepositoryMock.getData().task);
    }

    testNokAsNotFoundByFakeUserUuid(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID);

        this.assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
        this.assertNull(graphqlResponseRepositoryMock.getData().user);
    }

    testNokAsBadRequestByInvalidEmailMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_EMAIL_MUTATION);

        this.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode());
        this.assertNotEmpty(graphqlResponseRepositoryMock.getFirstErrorDetails());
    }

    testNokAsConflictByExistingEmailMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_CONFLICT_BY_EXISTING_EMAIL_MUTATION);

        this.assertEquals(ExceptionEnum.CODE_CONFLICT, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    testNokAsBadRequestByInvalidTaskStatusMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_TASK_STATUS_MUTATION);

        this.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    testNokAsGraphqlErrorByUnknownField(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_GRAPHQL_ERROR_BY_UNKNOWN_FIELD);

        this.assertNotEmpty(graphqlResponseRepositoryMock.getErrors());
        this.assertEquals(0, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    testOkAsSuccessByUpdateUserMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_UPDATE_USER_MUTATION);

        this.assertEmpty(graphqlResponseRepositoryMock.getErrors());
        const updatedUser = graphqlResponseRepositoryMock.getData().updateUser;
        this.assertEquals(UsersRawRepositoryMock.USER_NAME_UPDATED, updatedUser.user_name);
        this.assertEquals(UsersRawRepositoryMock.USER_EMAIL_UPDATED, updatedUser.user_email);
    }

    testOkAsSuccessByDeleteUserMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_DELETE_USER_MUTATION);

        this.assertEmpty(graphqlResponseRepositoryMock.getErrors());
        this.assertEquals(1, graphqlResponseRepositoryMock.getData().deleteUser.affected);
    }

    testOkAsSuccessByRegenerateApiTokenMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_REGENERATE_API_TOKEN_MUTATION);

        this.assertEmpty(graphqlResponseRepositoryMock.getErrors());
        const newApiToken = graphqlResponseRepositoryMock.getData().regenerateApiToken.api_token;
        this.assertNotEmpty(newApiToken);
        this.assertNotEquals(UsersRawRepositoryMock.USER_API_TOKEN_TO_REGENERATE, newApiToken);
    }

    testOkAsSuccessByUpdateTaskMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.OK_AS_SUCCESS_BY_UPDATE_TASK_MUTATION);

        this.assertEmpty(graphqlResponseRepositoryMock.getErrors());
        this.assertEquals(TaskStatusEnum.DONE, graphqlResponseRepositoryMock.getData().updateTask.task_status);
    }

    testNokAsConflictByExistingUserNameMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_CONFLICT_BY_EXISTING_USER_NAME_MUTATION);

        this.assertEquals(ExceptionEnum.CODE_CONFLICT, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    testNokAsBadRequestByInvalidUserNameAndShortPasswordMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_USER_NAME_AND_SHORT_PASSWORD_MUTATION);

        this.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode());
        const errorDetails = graphqlResponseRepositoryMock.getFirstErrorDetails().join(" ");
        this.assertStringContainsString("user_name:", errorDetails);
        this.assertStringContainsString("password:", errorDetails);
    }

    testNokAsBadRequestByEmptyUserNameUpdateUserMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_EMPTY_USER_NAME_UPDATE_USER_MUTATION);

        this.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    testNokAsConflictByEmailOfOtherUserUpdateUserMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_CONFLICT_BY_EMAIL_OF_OTHER_USER_UPDATE_USER_MUTATION);

        this.assertEquals(ExceptionEnum.CODE_CONFLICT, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    testNokAsNotFoundByOtherUserUuidUpdateUserMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_UPDATE_USER_MUTATION);

        this.assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
        this.assertNull(graphqlResponseRepositoryMock.getData().updateUser);
    }

    testNokAsNotFoundByOtherUserUuidRegenerateApiTokenMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_REGENERATE_API_TOKEN_MUTATION);

        this.assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    testNokAsNotFoundByOtherUserUuid(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID);

        this.assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
        this.assertNull(graphqlResponseRepositoryMock.getData().user);
    }

    testNokAsNotFoundByOtherUserUuidUserTasksQuery(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_USER_TASKS_QUERY);

        this.assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    testNokAsNotFoundByFakeTaskUuid(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID);

        this.assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
        this.assertNull(graphqlResponseRepositoryMock.getData().task);
    }

    testNokAsBadRequestByInvalidTargetDateMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_TARGET_DATE_MUTATION);

        this.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    testNokAsBadRequestByInvalidTaskTypeUpdateTaskMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_TASK_TYPE_UPDATE_TASK_MUTATION);

        this.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    testNokAsNotFoundByTaskOfOtherUserUpdateTaskMutation(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER_UPDATE_TASK_MUTATION);

        this.assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode());
        this.assertNull(graphqlResponseRepositoryMock.getData().updateTask);
    }

    testNokAsUnauthorizedByInvalidToken(): void {
        const graphqlResponseRepositoryMock = this.#getGraphqlResponseByTestCase(GraphqlTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_INVALID_TOKEN);

        this.assertEquals(ExceptionEnum.CODE_UNAUTHORIZED, graphqlResponseRepositoryMock.getFirstErrorCode());
    }

    testNokAsBadRequestByEmptyQuery(): void {
        GraphqlRequestMother.getInstance().getRequestByTestCase(GraphqlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_EMPTY_QUERY);

        const httpResponse = GraphqlControllerMother.getControllerInstance().execute();

        this.assertInstanceOf(HttpResponder, httpResponse);
        this.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, httpResponse.getStatusCode());
    }

    /**
     * Toda peticion GraphQL que se ejecuta responde 200; lo que cambia es data / errors.
     */
    #getGraphqlResponseByTestCase(graphqlTestCaseEnum: GraphqlTestCase): GraphqlResponseRepositoryMock {
        GraphqlRequestMother.getInstance().getRequestByTestCase(graphqlTestCaseEnum);

        const httpResponse = GraphqlControllerMother.getControllerInstance().execute();

        this.assertInstanceOf(HttpResponder, httpResponse);
        this.assertEquals(ExceptionEnum.CODE_OK, httpResponse.getStatusCode());
        return GraphqlResponseRepositoryMock.fromHttpResponse(httpResponse);
    }
}

AbstractTestCase.runTests(GraphqlControllerTest);
