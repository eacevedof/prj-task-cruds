<?php

declare(strict_types=1);

namespace Tests\Modules\Graphql\Infrastructure\Controllers\GraphqlController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Tasks\Domain\Entities\TaskEntity;
use App\Modules\Tasks\Domain\Enums\TaskStatusEnum;
use App\Modules\Tasks\Domain\Enums\TaskTypeEnum;
use Tests\AbstractTestCase;
use Tests\Modules\Graphql\Infrastructure\Controllers\GraphqlController\Mothers\{GraphqlControllerMother, GraphqlRequestMother, GraphqlTestCaseEnum};
use Tests\Modules\Graphql\Infrastructure\Repositories\GraphqlResponseRepositoryMock;
use Tests\Modules\Shared\Infrastructure\Repositories\TasksRawRepositoryMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;

/**
 * GraphQL responde 200 tambien con errores de dominio: el codigo HTTP equivalente va en errors[].extensions.code
 */
final class GraphqlControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByUsersQuery(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_USERS_QUERY);

        $this->assertEmpty($graphqlResponseRepositoryMock->getErrors());
        $this->assertContains(
            UsersRawRepositoryMock::USER_UUID_OK,
            array_column($graphqlResponseRepositoryMock->getData()["users"], "user_uuid")
        );
    }

    public function testOkAsSuccessByUserWithTasksQuery(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_USER_WITH_TASKS_QUERY);

        $this->assertEmpty($graphqlResponseRepositoryMock->getErrors());
        $this->assertContains(
            TasksRawRepositoryMock::TASK_UUID_OK,
            array_column($graphqlResponseRepositoryMock->getData()["user"]["tasks"]["items"], "task_uuid")
        );
    }

    public function testOkAsSuccessBySearchWithoutFiltersNewestFirst(): void
    {
        $taskPage = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_SEARCH_WITHOUT_FILTERS_NEWEST_FIRST)
            ->getData()["tasks"];

        $this->assertEquals(
            [TasksRawRepositoryMock::SEARCH_TITLE_GAMMA, TasksRawRepositoryMock::SEARCH_TITLE_BETA, TasksRawRepositoryMock::SEARCH_TITLE_ALPHA],
            array_column($taskPage["items"], "task_title")
        );
        $taskIds = array_column($taskPage["items"], "task_id");
        $taskIdsDesc = $taskIds;
        rsort($taskIdsDesc);
        $this->assertSame($taskIdsDesc, $taskIds);
        $this->assertEquals([1, 20, 3, 1], [$taskPage["page"], $taskPage["page_size"], $taskPage["total_items"], $taskPage["total_pages"]]);
    }

    public function testOkAsSuccessBySearchSecondPage(): void
    {
        $taskPage = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_SEARCH_SECOND_PAGE)
            ->getData()["tasks"];

        $this->assertEquals([TasksRawRepositoryMock::SEARCH_TITLE_ALPHA], array_column($taskPage["items"], "task_title"));
        $this->assertEquals([2, 3, 2], [$taskPage["page"], $taskPage["total_items"], $taskPage["total_pages"]]);
    }

    public function testOkAsSuccessBySearchFilteredByTextAndStatus(): void
    {
        $taskPage = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TEXT_AND_STATUS)
            ->getData()["tasks"];

        $this->assertEquals(
            [TasksRawRepositoryMock::SEARCH_TITLE_GAMMA, TasksRawRepositoryMock::SEARCH_TITLE_ALPHA],
            array_column($taskPage["items"], "task_title")
        );
        $this->assertEquals(2, $taskPage["total_items"]);
    }

    public function testOkAsSuccessBySearchFilteredByTargetDateRange(): void
    {
        $taskPage = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TARGET_DATE_RANGE)
            ->getData()["tasks"];

        $this->assertEquals([TasksRawRepositoryMock::SEARCH_TITLE_ALPHA], array_column($taskPage["items"], "task_title"));
    }

    public function testOkAsSuccessBySearchTextWithLikeWildcard(): void
    {
        $taskPage = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_SEARCH_TEXT_WITH_LIKE_WILDCARD)
            ->getData()["tasks"];

        // "%" se busca literal: solo la descripcion que lo contiene, no todas
        $this->assertEquals([TasksRawRepositoryMock::SEARCH_TITLE_GAMMA], array_column($taskPage["items"], "task_title"));
    }

    public function testNokAsBadRequestByInvalidPagination(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_PAGINATION);

        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $graphqlResponseRepositoryMock->getFirstErrorCode());
        // los dos campos erroneos viajan en el mensaje de la FieldsException
        $errorDetails = $graphqlResponseRepositoryMock->getFirstErrorDetails();
        $this->assertCount(1, $errorDetails);
        $this->assertStringContainsString("page:", $errorDetails[0]);
        $this->assertStringContainsString("page_size:", $errorDetails[0]);
    }

    public function testOkAsSuccessByTaskQueryWithVariables(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_TASK_QUERY_WITH_VARIABLES);

        $this->assertEmpty($graphqlResponseRepositoryMock->getErrors());
        $this->assertEquals(TasksRawRepositoryMock::TASK_TITLE_OK, $graphqlResponseRepositoryMock->getData()["task"]["task_title"]);
    }

    public function testOkAsSuccessByCreateUserMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_CREATE_USER_MUTATION);

        $this->assertEmpty($graphqlResponseRepositoryMock->getErrors());
        $this->assertEquals(
            UsersRawRepositoryMock::USER_EMAIL_NEW_GRAPHQL,
            $graphqlResponseRepositoryMock->getData()["createUser"]["user_email"]
        );
    }

    public function testOkAsSuccessByCreateTaskMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_CREATE_TASK_MUTATION);

        $this->assertEmpty($graphqlResponseRepositoryMock->getErrors());
        $createdTask = $graphqlResponseRepositoryMock->getData()["createTask"];
        $this->assertEquals(UsersRawRepositoryMock::USER_UUID_OK, $createdTask["user_uuid"]);
        $this->assertStringStartsWith(TaskEntity::TASK_UUID_PREFIX, $createdTask["task_uuid"]);
        $this->assertEquals(TaskStatusEnum::DEFAULT, $createdTask["task_status"]);
        $this->assertEquals(TaskTypeEnum::DEFAULT, $createdTask["task_type"]);
    }

    public function testOkAsSuccessByDeleteTaskMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_DELETE_TASK_MUTATION);

        $this->assertEmpty($graphqlResponseRepositoryMock->getErrors());
        $this->assertEquals(1, $graphqlResponseRepositoryMock->getData()["deleteTask"]["affected"]);
    }

    public function testOkAsSuccessByCreateUserMutationWithoutToken(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_CREATE_USER_MUTATION_WITHOUT_TOKEN);

        $this->assertEmpty($graphqlResponseRepositoryMock->getErrors());
        $this->assertNotEmpty($graphqlResponseRepositoryMock->getData()["createUser"]["api_token"]);
    }

    public function testOkAsSuccessByLogInMutationWithoutToken(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_LOG_IN_MUTATION_WITHOUT_TOKEN);

        $this->assertEmpty($graphqlResponseRepositoryMock->getErrors());
        $loggedInUser = $graphqlResponseRepositoryMock->getData()["logIn"];
        $this->assertEquals(UsersRawRepositoryMock::USER_UUID_TO_LOG_IN, $loggedInUser["user_uuid"]);
        // token nuevo: el de los fixtures deja de valer
        $this->assertNotEquals(UsersRawRepositoryMock::USER_API_TOKEN_TO_LOG_IN, $loggedInUser["api_token"]);
    }

    public function testNokAsUnauthorizedByWrongPasswordLogInMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD_LOG_IN_MUTATION);

        $this->assertEquals(ExceptionEnum::CODE_UNAUTHORIZED, $graphqlResponseRepositoryMock->getFirstErrorCode());
        $this->assertNull($graphqlResponseRepositoryMock->getData()["logIn"]);
    }

    public function testNokAsUnauthorizedByMissingToken(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN);

        $this->assertEquals(ExceptionEnum::CODE_UNAUTHORIZED, $graphqlResponseRepositoryMock->getFirstErrorCode());
    }

    public function testNokAsNotFoundByTaskOfOtherUser(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER);

        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $graphqlResponseRepositoryMock->getFirstErrorCode());
        $this->assertNull($graphqlResponseRepositoryMock->getData()["task"]);
    }

    public function testNokAsNotFoundByFakeUserUuid(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID);

        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $graphqlResponseRepositoryMock->getFirstErrorCode());
        $this->assertNull($graphqlResponseRepositoryMock->getData()["user"]);
    }

    public function testNokAsBadRequestByInvalidEmailMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_EMAIL_MUTATION);

        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $graphqlResponseRepositoryMock->getFirstErrorCode());
        $this->assertNotEmpty($graphqlResponseRepositoryMock->getFirstErrorDetails());
    }

    public function testNokAsConflictByExistingEmailMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_CONFLICT_BY_EXISTING_EMAIL_MUTATION);

        $this->assertEquals(ExceptionEnum::CODE_CONFLICT, $graphqlResponseRepositoryMock->getFirstErrorCode());
    }

    public function testNokAsBadRequestByInvalidTaskStatusMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_TASK_STATUS_MUTATION);

        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $graphqlResponseRepositoryMock->getFirstErrorCode());
    }

    public function testNokAsGraphqlErrorByUnknownField(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_GRAPHQL_ERROR_BY_UNKNOWN_FIELD);

        $this->assertNotEmpty($graphqlResponseRepositoryMock->getErrors());
        $this->assertEquals(0, $graphqlResponseRepositoryMock->getFirstErrorCode());
    }

    public function testOkAsSuccessByUpdateUserMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_UPDATE_USER_MUTATION);

        $this->assertEmpty($graphqlResponseRepositoryMock->getErrors());
        $updatedUser = $graphqlResponseRepositoryMock->getData()["updateUser"];
        $this->assertEquals(UsersRawRepositoryMock::USER_NAME_UPDATED, $updatedUser["user_name"]);
        $this->assertEquals(UsersRawRepositoryMock::USER_EMAIL_UPDATED, $updatedUser["user_email"]);
    }

    public function testOkAsSuccessByDeleteUserMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_DELETE_USER_MUTATION);

        $this->assertEmpty($graphqlResponseRepositoryMock->getErrors());
        $this->assertEquals(1, $graphqlResponseRepositoryMock->getData()["deleteUser"]["affected"]);
    }

    public function testOkAsSuccessByRegenerateApiTokenMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_REGENERATE_API_TOKEN_MUTATION);

        $this->assertEmpty($graphqlResponseRepositoryMock->getErrors());
        $newApiToken = $graphqlResponseRepositoryMock->getData()["regenerateApiToken"]["api_token"];
        $this->assertNotEmpty($newApiToken);
        $this->assertNotEquals(UsersRawRepositoryMock::USER_API_TOKEN_TO_REGENERATE, $newApiToken);
    }

    public function testOkAsSuccessByUpdateTaskMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_UPDATE_TASK_MUTATION);

        $this->assertEmpty($graphqlResponseRepositoryMock->getErrors());
        $this->assertEquals(TaskStatusEnum::DONE, $graphqlResponseRepositoryMock->getData()["updateTask"]["task_status"]);
    }

    public function testNokAsConflictByExistingUserNameMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_CONFLICT_BY_EXISTING_USER_NAME_MUTATION);

        $this->assertEquals(ExceptionEnum::CODE_CONFLICT, $graphqlResponseRepositoryMock->getFirstErrorCode());
    }

    public function testNokAsBadRequestByInvalidUserNameAndShortPasswordMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_USER_NAME_AND_SHORT_PASSWORD_MUTATION);

        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $graphqlResponseRepositoryMock->getFirstErrorCode());
        $errorDetails = implode(" ", $graphqlResponseRepositoryMock->getFirstErrorDetails());
        $this->assertStringContainsString("user_name:", $errorDetails);
        $this->assertStringContainsString("password:", $errorDetails);
    }

    public function testNokAsBadRequestByEmptyUserNameUpdateUserMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_EMPTY_USER_NAME_UPDATE_USER_MUTATION);

        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $graphqlResponseRepositoryMock->getFirstErrorCode());
    }

    public function testNokAsConflictByEmailOfOtherUserUpdateUserMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_CONFLICT_BY_EMAIL_OF_OTHER_USER_UPDATE_USER_MUTATION);

        $this->assertEquals(ExceptionEnum::CODE_CONFLICT, $graphqlResponseRepositoryMock->getFirstErrorCode());
    }

    public function testNokAsNotFoundByOtherUserUuidUpdateUserMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_UPDATE_USER_MUTATION);

        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $graphqlResponseRepositoryMock->getFirstErrorCode());
        $this->assertNull($graphqlResponseRepositoryMock->getData()["updateUser"]);
    }

    public function testNokAsNotFoundByOtherUserUuidRegenerateApiTokenMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_REGENERATE_API_TOKEN_MUTATION);

        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $graphqlResponseRepositoryMock->getFirstErrorCode());
    }

    public function testNokAsNotFoundByOtherUserUuid(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID);

        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $graphqlResponseRepositoryMock->getFirstErrorCode());
        $this->assertNull($graphqlResponseRepositoryMock->getData()["user"]);
    }

    public function testNokAsNotFoundByOtherUserUuidUserTasksQuery(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_USER_TASKS_QUERY);

        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $graphqlResponseRepositoryMock->getFirstErrorCode());
    }

    public function testNokAsNotFoundByFakeTaskUuid(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID);

        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $graphqlResponseRepositoryMock->getFirstErrorCode());
        $this->assertNull($graphqlResponseRepositoryMock->getData()["task"]);
    }

    public function testNokAsBadRequestByInvalidTargetDateMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_TARGET_DATE_MUTATION);

        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $graphqlResponseRepositoryMock->getFirstErrorCode());
    }

    public function testNokAsBadRequestByInvalidTaskTypeUpdateTaskMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_TASK_TYPE_UPDATE_TASK_MUTATION);

        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $graphqlResponseRepositoryMock->getFirstErrorCode());
    }

    public function testNokAsNotFoundByTaskOfOtherUserUpdateTaskMutation(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER_UPDATE_TASK_MUTATION);

        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $graphqlResponseRepositoryMock->getFirstErrorCode());
        $this->assertNull($graphqlResponseRepositoryMock->getData()["updateTask"]);
    }

    public function testNokAsUnauthorizedByInvalidToken(): void
    {
        $graphqlResponseRepositoryMock = $this->getGraphqlResponseByTestCase(GraphqlTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_INVALID_TOKEN);

        $this->assertEquals(ExceptionEnum::CODE_UNAUTHORIZED, $graphqlResponseRepositoryMock->getFirstErrorCode());
    }

    public function testNokAsBadRequestByEmptyQuery(): void
    {
        $routeArgs = GraphqlRequestMother::getInstance()
            ->getRequestByTestCase(GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_EMPTY_QUERY);

        $httpResponse = GraphqlControllerMother::getControllerInstance()->execute(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $httpResponse->getStatusCode());
    }

    /**
     * Toda peticion GraphQL que se ejecuta responde 200; lo que cambia es data / errors.
     */
    private function getGraphqlResponseByTestCase(GraphqlTestCaseEnum $graphqlTestCaseEnum): GraphqlResponseRepositoryMock
    {
        $routeArgs = GraphqlRequestMother::getInstance()->getRequestByTestCase($graphqlTestCaseEnum);

        $httpResponse = GraphqlControllerMother::getControllerInstance()->execute(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
        return GraphqlResponseRepositoryMock::fromHttpResponse($httpResponse);
    }
}
