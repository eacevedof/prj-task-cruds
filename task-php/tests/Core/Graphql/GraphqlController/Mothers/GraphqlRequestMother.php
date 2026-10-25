<?php

declare(strict_types=1);

namespace Tests\Core\Graphql\GraphqlController\Mothers;

use App\Modules\Tasks\Domain\Enums\TaskStatusEnum;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;
use Tests\Modules\Shared\Infrastructure\Repositories\TasksRawRepositoryMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;

final class GraphqlRequestMother
{
    private const string MUTATION_CREATE_USER = "mutation(\$userName: String!, \$userEmail: String!, \$password: String!) { createUser(user_name: \$userName, user_email: \$userEmail, password: \$password) { user_uuid } }";
    private const string MUTATION_UPDATE_USER = "mutation(\$userUuid: ID!, \$userName: String!, \$userEmail: String!) { updateUser(user_uuid: \$userUuid, user_name: \$userName, user_email: \$userEmail) { user_uuid user_name user_email } }";
    private const string MUTATION_REGENERATE_API_TOKEN = "mutation(\$userUuid: ID!) { regenerateApiToken(user_uuid: \$userUuid) { user_uuid api_token } }";
    private const string MUTATION_UPDATE_TASK = "mutation(\$taskUuid: ID!, \$taskTitle: String!, \$taskStatus: String, \$taskType: String) { updateTask(task_uuid: \$taskUuid, task_title: \$taskTitle, task_status: \$taskStatus, task_type: \$taskType) { task_uuid task_title task_status } }";

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return array<string, string> argumentos de GraphqlController::execute() (ninguno: todo va en el body)
     */
    public function getRequestByTestCase(
        GraphqlTestCaseEnum $useCase
    ): array
    {
        $userUuidOk = UsersRawRepositoryMock::USER_UUID_OK;
        return match ($useCase) {
            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_USERS_QUERY =>
                $this->getGraphqlRequest("{ users { user_uuid user_name user_email } }"),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_USER_WITH_TASKS_QUERY =>
                $this->getGraphqlRequest("{ user(user_uuid: \"$userUuidOk\") { user_uuid tasks { items { task_uuid task_title } total_items } } }"),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_SEARCH_WITHOUT_FILTERS_NEWEST_FIRST =>
                $this->getSearchRequest("{ tasks { items { task_id task_title } page page_size total_items total_pages } }"),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_SEARCH_SECOND_PAGE =>
                $this->getSearchRequest("{ tasks(page: 2, page_size: 2) { items { task_title } page total_items total_pages } }"),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TEXT_AND_STATUS =>
                $this->getSearchRequest(
                    "query(\$filter: TasksFilter) { tasks(filter: \$filter) { items { task_title } total_items } }",
                    ["filter" => ["task_text" => "SEARCH", "task_status" => TaskStatusEnum::PENDING]]
                ),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TARGET_DATE_RANGE =>
                $this->getSearchRequest(
                    "query(\$filter: TasksFilter) { tasks(filter: \$filter) { items { task_title } total_items } }",
                    ["filter" => ["target_date_from" => "2026-01-01", "target_date_to" => "2026-04-01"]]
                ),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_SEARCH_TEXT_WITH_LIKE_WILDCARD =>
                $this->getSearchRequest("{ tasks(filter: {task_text: \"%\"}) { items { task_title } total_items } }"),

            GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_PAGINATION =>
                $this->getSearchRequest("{ tasks(page: 0, page_size: 101) { total_items } }"),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_TASK_QUERY_WITH_VARIABLES =>
                $this->getGraphqlRequest(
                    "query GetTask(\$taskUuid: ID!) { task(task_uuid: \$taskUuid) { task_uuid task_title } }",
                    ["taskUuid" => TasksRawRepositoryMock::TASK_UUID_OK]
                ),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_CREATE_USER_MUTATION =>
                $this->getGraphqlRequest(
                    "mutation(\$userName: String!, \$userEmail: String!, \$password: String!) { createUser(user_name: \$userName, user_email: \$userEmail, password: \$password) { user_uuid user_email } }",
                    [
                        "userName" => UsersRawRepositoryMock::USER_NAME_NEW_GRAPHQL,
                        "userEmail" => UsersRawRepositoryMock::USER_EMAIL_NEW_GRAPHQL,
                        "password" => UsersRawRepositoryMock::USER_PASSWORD_NEW,
                    ]
                ),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_CREATE_TASK_MUTATION =>
                $this->getGraphqlRequest(
                    "mutation { createTask(task_title: \"Task GraphQL\") { task_uuid user_uuid task_status task_type } }"
                ),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_DELETE_TASK_MUTATION =>
                $this->getGraphqlRequest(
                    "mutation(\$taskUuid: ID!) { deleteTask(task_uuid: \$taskUuid) { task_uuid affected } }",
                    ["taskUuid" => TasksRawRepositoryMock::TASK_UUID_TO_DELETE_GRAPHQL]
                ),

            GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID =>
                $this->getGraphqlRequest(
                    "query(\$userUuid: ID!) { user(user_uuid: \$userUuid) { user_uuid } }",
                    ["userUuid" => UsersRawRepositoryMock::USER_UUID_NOK]
                ),

            GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_EMAIL_MUTATION =>
                $this->getGraphqlRequest(
                    "mutation(\$userName: String!, \$userEmail: String!, \$password: String!) { createUser(user_name: \$userName, user_email: \$userEmail, password: \$password) { user_uuid } }",
                    [
                        "userName" => UsersRawRepositoryMock::USER_NAME_OTHER,
                        "userEmail" => UsersRawRepositoryMock::USER_EMAIL_INVALID,
                        "password" => UsersRawRepositoryMock::USER_PASSWORD_NEW,
                    ]
                ),

            GraphqlTestCaseEnum::NOK_AS_CONFLICT_BY_EXISTING_EMAIL_MUTATION =>
                $this->getGraphqlRequest(
                    "mutation(\$userName: String!, \$userEmail: String!, \$password: String!) { createUser(user_name: \$userName, user_email: \$userEmail, password: \$password) { user_uuid } }",
                    [
                        "userName" => UsersRawRepositoryMock::USER_NAME_OTHER,
                        "userEmail" => UsersRawRepositoryMock::USER_EMAIL_OK,
                        "password" => UsersRawRepositoryMock::USER_PASSWORD_NEW,
                    ]
                ),

            GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_TASK_STATUS_MUTATION =>
                $this->getGraphqlRequest(
                    "mutation(\$taskStatus: String) { createTask(task_title: \"Task GraphQL\", task_status: \$taskStatus) { task_uuid } }",
                    ["taskStatus" => TasksRawRepositoryMock::TASK_STATUS_INVALID]
                ),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_CREATE_USER_MUTATION_WITHOUT_TOKEN =>
                $this->getGraphqlRequest(
                    "mutation(\$userName: String!, \$userEmail: String!, \$password: String!) { createUser(user_name: \$userName, user_email: \$userEmail, password: \$password) { user_uuid api_token } }",
                    [
                        "userName" => UsersRawRepositoryMock::USER_NAME_NEW_GRAPHQL_WITHOUT_TOKEN,
                        "userEmail" => UsersRawRepositoryMock::USER_EMAIL_NEW_GRAPHQL_WITHOUT_TOKEN,
                        "password" => UsersRawRepositoryMock::USER_PASSWORD_NEW,
                    ],
                    ""
                ),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_LOG_IN_MUTATION_WITHOUT_TOKEN =>
                $this->getGraphqlRequest(
                    "mutation(\$userName: String!, \$password: String!) { logIn(user_name: \$userName, password: \$password) { user_uuid api_token } }",
                    [
                        "userName" => UsersRawRepositoryMock::USER_NAME_TO_LOG_IN,
                        "password" => UsersRawRepositoryMock::USER_PASSWORD_TO_LOG_IN,
                    ],
                    ""
                ),

            GraphqlTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD_LOG_IN_MUTATION =>
                $this->getGraphqlRequest(
                    "mutation(\$userName: String!, \$password: String!) { logIn(user_name: \$userName, password: \$password) { user_uuid } }",
                    [
                        "userName" => UsersRawRepositoryMock::USER_NAME_TO_LOG_IN,
                        "password" => UsersRawRepositoryMock::USER_PASSWORD_NOK,
                    ],
                    ""
                ),

            GraphqlTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN =>
                $this->getGraphqlRequest("{ users { user_uuid } }", [], ""),

            GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER =>
                $this->getGraphqlRequest(
                    "query(\$taskUuid: ID!) { task(task_uuid: \$taskUuid) { task_uuid } }",
                    ["taskUuid" => TasksRawRepositoryMock::TASK_UUID_OK],
                    UsersRawRepositoryMock::USER_API_TOKEN_TO_UPDATE
                ),

            GraphqlTestCaseEnum::NOK_AS_GRAPHQL_ERROR_BY_UNKNOWN_FIELD =>
                $this->getGraphqlRequest("{ users { unknown_field } }"),

            GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_EMPTY_QUERY =>
                $this->getGraphqlRequest("   "),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_UPDATE_USER_MUTATION =>
                $this->getGraphqlRequest(
                    self::MUTATION_UPDATE_USER,
                    [
                        "userUuid" => UsersRawRepositoryMock::USER_UUID_TO_UPDATE,
                        "userName" => UsersRawRepositoryMock::USER_NAME_UPDATED,
                        "userEmail" => UsersRawRepositoryMock::USER_EMAIL_UPDATED,
                    ],
                    UsersRawRepositoryMock::USER_API_TOKEN_TO_UPDATE
                ),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_DELETE_USER_MUTATION =>
                $this->getGraphqlRequest(
                    "mutation(\$userUuid: ID!) { deleteUser(user_uuid: \$userUuid) { user_uuid affected } }",
                    ["userUuid" => UsersRawRepositoryMock::USER_UUID_TO_DELETE],
                    UsersRawRepositoryMock::USER_API_TOKEN_TO_DELETE
                ),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_REGENERATE_API_TOKEN_MUTATION =>
                $this->getGraphqlRequest(
                    self::MUTATION_REGENERATE_API_TOKEN,
                    ["userUuid" => UsersRawRepositoryMock::USER_UUID_TO_REGENERATE],
                    UsersRawRepositoryMock::USER_API_TOKEN_TO_REGENERATE
                ),

            GraphqlTestCaseEnum::OK_AS_SUCCESS_BY_UPDATE_TASK_MUTATION =>
                $this->getGraphqlRequest(
                    self::MUTATION_UPDATE_TASK,
                    [
                        "taskUuid" => TasksRawRepositoryMock::TASK_UUID_TO_UPDATE,
                        "taskTitle" => "Task actualizada",
                        "taskStatus" => TaskStatusEnum::DONE,
                    ]
                ),

            GraphqlTestCaseEnum::NOK_AS_CONFLICT_BY_EXISTING_USER_NAME_MUTATION =>
                $this->getGraphqlRequest(
                    self::MUTATION_CREATE_USER,
                    [
                        "userName" => UsersRawRepositoryMock::USER_NAME_OK,
                        "userEmail" => UsersRawRepositoryMock::USER_EMAIL_NEW,
                        "password" => UsersRawRepositoryMock::USER_PASSWORD_NEW,
                    ],
                    ""
                ),

            GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_USER_NAME_AND_SHORT_PASSWORD_MUTATION =>
                $this->getGraphqlRequest(
                    self::MUTATION_CREATE_USER,
                    [
                        "userName" => UsersRawRepositoryMock::USER_NAME_INVALID,
                        "userEmail" => UsersRawRepositoryMock::USER_EMAIL_NEW,
                        "password" => "short",
                    ],
                    ""
                ),

            GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_EMPTY_USER_NAME_UPDATE_USER_MUTATION =>
                $this->getGraphqlRequest(
                    self::MUTATION_UPDATE_USER,
                    [
                        "userUuid" => UsersRawRepositoryMock::USER_UUID_TO_UPDATE,
                        "userName" => "  ",
                        "userEmail" => UsersRawRepositoryMock::USER_EMAIL_UPDATED,
                    ],
                    UsersRawRepositoryMock::USER_API_TOKEN_TO_UPDATE
                ),

            GraphqlTestCaseEnum::NOK_AS_CONFLICT_BY_EMAIL_OF_OTHER_USER_UPDATE_USER_MUTATION =>
                $this->getGraphqlRequest(
                    self::MUTATION_UPDATE_USER,
                    [
                        "userUuid" => UsersRawRepositoryMock::USER_UUID_TO_UPDATE,
                        "userName" => UsersRawRepositoryMock::USER_NAME_UPDATED,
                        "userEmail" => UsersRawRepositoryMock::USER_EMAIL_OK,
                    ],
                    UsersRawRepositoryMock::USER_API_TOKEN_TO_UPDATE
                ),

            GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_UPDATE_USER_MUTATION =>
                $this->getGraphqlRequest(
                    self::MUTATION_UPDATE_USER,
                    [
                        "userUuid" => UsersRawRepositoryMock::USER_UUID_TO_UPDATE,
                        "userName" => UsersRawRepositoryMock::USER_NAME_OTHER,
                        "userEmail" => UsersRawRepositoryMock::USER_EMAIL_NEW,
                    ]
                ),

            GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_REGENERATE_API_TOKEN_MUTATION =>
                $this->getGraphqlRequest(
                    self::MUTATION_REGENERATE_API_TOKEN,
                    ["userUuid" => UsersRawRepositoryMock::USER_UUID_TO_REGENERATE]
                ),

            GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID =>
                $this->getGraphqlRequest(
                    "query(\$userUuid: ID!) { user(user_uuid: \$userUuid) { user_uuid } }",
                    ["userUuid" => UsersRawRepositoryMock::USER_UUID_TO_UPDATE]
                ),

            GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_USER_TASKS_QUERY =>
                $this->getGraphqlRequest(
                    "query(\$userUuid: ID!) { userTasks(user_uuid: \$userUuid) { total_items } }",
                    ["userUuid" => UsersRawRepositoryMock::USER_UUID_TO_UPDATE]
                ),

            GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID =>
                $this->getGraphqlRequest(
                    "query(\$taskUuid: ID!) { task(task_uuid: \$taskUuid) { task_uuid } }",
                    ["taskUuid" => TasksRawRepositoryMock::TASK_UUID_NOK]
                ),

            GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_TARGET_DATE_MUTATION =>
                $this->getGraphqlRequest(
                    "mutation(\$targetDate: String) { createTask(task_title: \"Task GraphQL\", target_date: \$targetDate) { task_uuid } }",
                    ["targetDate" => TasksRawRepositoryMock::TARGET_DATE_INVALID]
                ),

            GraphqlTestCaseEnum::NOK_AS_BAD_REQUEST_BY_INVALID_TASK_TYPE_UPDATE_TASK_MUTATION =>
                $this->getGraphqlRequest(
                    self::MUTATION_UPDATE_TASK,
                    [
                        "taskUuid" => TasksRawRepositoryMock::TASK_UUID_TO_UPDATE,
                        "taskTitle" => "Task actualizada",
                        "taskType" => TasksRawRepositoryMock::TASK_TYPE_INVALID,
                    ]
                ),

            GraphqlTestCaseEnum::NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER_UPDATE_TASK_MUTATION =>
                $this->getGraphqlRequest(
                    self::MUTATION_UPDATE_TASK,
                    [
                        "taskUuid" => TasksRawRepositoryMock::TASK_UUID_TO_UPDATE,
                        "taskTitle" => "Task de otro",
                    ],
                    UsersRawRepositoryMock::USER_API_TOKEN_TO_UPDATE
                ),

            GraphqlTestCaseEnum::NOK_AS_UNAUTHORIZED_BY_INVALID_TOKEN =>
                $this->getGraphqlRequest("{ users { user_uuid } }", [], UsersRawRepositoryMock::USER_API_TOKEN_NOK),
        };
    }

    /**
     * Las busquedas van con el dueño de las tasks de busqueda: sus totales no cambian por otros tests.
     */
    private function getSearchRequest(string $query, array $variables = []): array
    {
        return $this->getGraphqlRequest($query, $variables, UsersRawRepositoryMock::USER_API_TOKEN_SEARCH);
    }

    /**
     * Por defecto con el token del usuario OK; "" para probar sin autenticar.
     */
    private function getGraphqlRequest(
        string $query,
        array $variables = [],
        string $bearerToken = UsersRawRepositoryMock::USER_API_TOKEN_OK
    ): array
    {
        return HttpRequestMock::getInstance()
            ->setBearerToken($bearerToken)
            ->setPostRawParams(["query" => $query, "variables" => $variables])
            ->getMockedInstance();
    }
}
