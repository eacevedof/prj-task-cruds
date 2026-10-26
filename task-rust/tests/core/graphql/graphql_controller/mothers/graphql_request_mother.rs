use std::sync::Arc;

use serde_json::{Value, json};

use task_rust::modules::shared::application::primitives::get_primitives;
use task_rust::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequester;
use task_rust::modules::tasks::domain::enums::task_status_enum::TaskStatusEnum;

use crate::mothers::graphql_test_case_enum::GraphqlTestCaseEnum;
use crate::tests::modules::shared::infrastructure::components::http_request_mock::HttpRequestMock;
use crate::tests::modules::shared::infrastructure::repositories::tasks_raw_repository_mock::TasksRawRepositoryMock as Tasks;
use crate::tests::modules::shared::infrastructure::repositories::users_raw_repository_mock::UsersRawRepositoryMock as Users;

pub const MUTATION_CREATE_USER: &str = "mutation($userName: String!, $userEmail: String!, $password: String!) { createUser(user_name: $userName, user_email: $userEmail, password: $password) { user_uuid } }";
pub const MUTATION_UPDATE_USER: &str = "mutation($userUuid: ID!, $userName: String!, $userEmail: String!) { updateUser(user_uuid: $userUuid, user_name: $userName, user_email: $userEmail) { user_uuid user_name user_email } }";
pub const MUTATION_REGENERATE_API_TOKEN: &str =
    "mutation($userUuid: ID!) { regenerateApiToken(user_uuid: $userUuid) { user_uuid api_token } }";
pub const MUTATION_UPDATE_TASK: &str = "mutation($taskUuid: ID!, $taskTitle: String!, $taskStatus: String, $taskType: String) { updateTask(task_uuid: $taskUuid, task_title: $taskTitle, task_status: $taskStatus, task_type: $taskType) { task_uuid task_title task_status } }";

pub struct GraphqlRequestMother;

impl GraphqlRequestMother {
    pub fn get_instance() -> Self {
        Self
    }

    /// el HttpRequester de la peticion (GraphqlController::execute() no tiene argumentos: todo va en el body).
    pub fn get_request_by_test_case(&self, use_case: GraphqlTestCaseEnum) -> Arc<HttpRequester> {
        use GraphqlTestCaseEnum::*;
        match use_case {
            OK_AS_SUCCESS_BY_USERS_QUERY => {
                self.get_graphql_request("{ users { user_uuid user_name user_email } }", json!({}), Users::USER_API_TOKEN_OK)
            }

            OK_AS_SUCCESS_BY_USER_WITH_TASKS_QUERY => self.get_graphql_request(
                &format!(
                    "{{ user(user_uuid: \"{}\") {{ user_uuid tasks {{ items {{ task_uuid task_title }} total_items }} }} }}",
                    Users::USER_UUID_OK
                ),
                json!({}),
                Users::USER_API_TOKEN_OK,
            ),

            OK_AS_SUCCESS_BY_SEARCH_WITHOUT_FILTERS_NEWEST_FIRST => self.get_search_request(
                "{ tasks { items { task_id task_title } page page_size total_items total_pages } }",
                json!({}),
            ),

            OK_AS_SUCCESS_BY_SEARCH_SECOND_PAGE => self.get_search_request(
                "{ tasks(page: 2, page_size: 2) { items { task_title } page total_items total_pages } }",
                json!({}),
            ),

            OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TEXT_AND_STATUS => self.get_search_request(
                "query($filter: TasksFilter) { tasks(filter: $filter) { items { task_title } total_items } }",
                json!({"filter": {"task_text": "SEARCH", "task_status": TaskStatusEnum::PENDING}}),
            ),

            OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TARGET_DATE_RANGE => self.get_search_request(
                "query($filter: TasksFilter) { tasks(filter: $filter) { items { task_title } total_items } }",
                json!({"filter": {"target_date_from": "2026-01-01", "target_date_to": "2026-04-01"}}),
            ),

            OK_AS_SUCCESS_BY_SEARCH_TEXT_WITH_LIKE_WILDCARD => self.get_search_request(
                r#"{ tasks(filter: {task_text: "%"}) { items { task_title } total_items } }"#,
                json!({}),
            ),

            NOK_AS_BAD_REQUEST_BY_INVALID_PAGINATION => {
                self.get_search_request("{ tasks(page: 0, page_size: 101) { total_items } }", json!({}))
            }

            OK_AS_SUCCESS_BY_TASK_QUERY_WITH_VARIABLES => self.get_graphql_request(
                "query GetTask($taskUuid: ID!) { task(task_uuid: $taskUuid) { task_uuid task_title } }",
                json!({"taskUuid": Tasks::TASK_UUID_OK}),
                Users::USER_API_TOKEN_OK,
            ),

            OK_AS_SUCCESS_BY_CREATE_USER_MUTATION => self.get_graphql_request(
                "mutation($userName: String!, $userEmail: String!, $password: String!) { createUser(user_name: $userName, user_email: $userEmail, password: $password) { user_uuid user_email } }",
                json!({
                    "userName": Users::USER_NAME_NEW_GRAPHQL,
                    "userEmail": Users::USER_EMAIL_NEW_GRAPHQL,
                    "password": Users::USER_PASSWORD_NEW,
                }),
                Users::USER_API_TOKEN_OK,
            ),

            OK_AS_SUCCESS_BY_CREATE_TASK_MUTATION => self.get_graphql_request(
                r#"mutation { createTask(task_title: "Task GraphQL") { task_uuid user_uuid task_status task_type } }"#,
                json!({}),
                Users::USER_API_TOKEN_OK,
            ),

            OK_AS_SUCCESS_BY_DELETE_TASK_MUTATION => self.get_graphql_request(
                "mutation($taskUuid: ID!) { deleteTask(task_uuid: $taskUuid) { task_uuid affected } }",
                json!({"taskUuid": Tasks::TASK_UUID_TO_DELETE_GRAPHQL}),
                Users::USER_API_TOKEN_OK,
            ),

            NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID => self.get_graphql_request(
                "query($userUuid: ID!) { user(user_uuid: $userUuid) { user_uuid } }",
                json!({"userUuid": Users::USER_UUID_NOK}),
                Users::USER_API_TOKEN_OK,
            ),

            NOK_AS_BAD_REQUEST_BY_INVALID_EMAIL_MUTATION => self.get_graphql_request(
                MUTATION_CREATE_USER,
                json!({
                    "userName": Users::USER_NAME_OTHER,
                    "userEmail": Users::USER_EMAIL_INVALID,
                    "password": Users::USER_PASSWORD_NEW,
                }),
                Users::USER_API_TOKEN_OK,
            ),

            NOK_AS_CONFLICT_BY_EXISTING_EMAIL_MUTATION => self.get_graphql_request(
                MUTATION_CREATE_USER,
                json!({
                    "userName": Users::USER_NAME_OTHER,
                    "userEmail": Users::USER_EMAIL_OK,
                    "password": Users::USER_PASSWORD_NEW,
                }),
                Users::USER_API_TOKEN_OK,
            ),

            NOK_AS_BAD_REQUEST_BY_INVALID_TASK_STATUS_MUTATION => self.get_graphql_request(
                r#"mutation($taskStatus: String) { createTask(task_title: "Task GraphQL", task_status: $taskStatus) { task_uuid } }"#,
                json!({"taskStatus": Tasks::TASK_STATUS_INVALID}),
                Users::USER_API_TOKEN_OK,
            ),

            OK_AS_SUCCESS_BY_CREATE_USER_MUTATION_WITHOUT_TOKEN => self.get_graphql_request(
                "mutation($userName: String!, $userEmail: String!, $password: String!) { createUser(user_name: $userName, user_email: $userEmail, password: $password) { user_uuid api_token } }",
                json!({
                    "userName": Users::USER_NAME_NEW_GRAPHQL_WITHOUT_TOKEN,
                    "userEmail": Users::USER_EMAIL_NEW_GRAPHQL_WITHOUT_TOKEN,
                    "password": Users::USER_PASSWORD_NEW,
                }),
                "",
            ),

            OK_AS_SUCCESS_BY_LOG_IN_MUTATION_WITHOUT_TOKEN => self.get_graphql_request(
                "mutation($userName: String!, $password: String!) { logIn(user_name: $userName, password: $password) { user_uuid api_token } }",
                json!({"userName": Users::USER_NAME_TO_LOG_IN, "password": Users::USER_PASSWORD_TO_LOG_IN}),
                "",
            ),

            NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD_LOG_IN_MUTATION => self.get_graphql_request(
                "mutation($userName: String!, $password: String!) { logIn(user_name: $userName, password: $password) { user_uuid } }",
                json!({"userName": Users::USER_NAME_TO_LOG_IN, "password": Users::USER_PASSWORD_NOK}),
                "",
            ),

            NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN => self.get_graphql_request("{ users { user_uuid } }", json!({}), ""),

            NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER => self.get_graphql_request(
                "query($taskUuid: ID!) { task(task_uuid: $taskUuid) { task_uuid } }",
                json!({"taskUuid": Tasks::TASK_UUID_OK}),
                Users::USER_API_TOKEN_TO_UPDATE,
            ),

            NOK_AS_GRAPHQL_ERROR_BY_UNKNOWN_FIELD => {
                self.get_graphql_request("{ users { unknown_field } }", json!({}), Users::USER_API_TOKEN_OK)
            }

            NOK_AS_BAD_REQUEST_BY_EMPTY_QUERY => self.get_graphql_request("   ", json!({}), Users::USER_API_TOKEN_OK),

            OK_AS_SUCCESS_BY_UPDATE_USER_MUTATION => self.get_graphql_request(
                MUTATION_UPDATE_USER,
                json!({
                    "userUuid": Users::USER_UUID_TO_UPDATE,
                    "userName": Users::USER_NAME_UPDATED,
                    "userEmail": Users::USER_EMAIL_UPDATED,
                }),
                Users::USER_API_TOKEN_TO_UPDATE,
            ),

            OK_AS_SUCCESS_BY_DELETE_USER_MUTATION => self.get_graphql_request(
                "mutation($userUuid: ID!) { deleteUser(user_uuid: $userUuid) { user_uuid affected } }",
                json!({"userUuid": Users::USER_UUID_TO_DELETE}),
                Users::USER_API_TOKEN_TO_DELETE,
            ),

            OK_AS_SUCCESS_BY_REGENERATE_API_TOKEN_MUTATION => self.get_graphql_request(
                MUTATION_REGENERATE_API_TOKEN,
                json!({"userUuid": Users::USER_UUID_TO_REGENERATE}),
                Users::USER_API_TOKEN_TO_REGENERATE,
            ),

            OK_AS_SUCCESS_BY_UPDATE_TASK_MUTATION => self.get_graphql_request(
                MUTATION_UPDATE_TASK,
                json!({
                    "taskUuid": Tasks::TASK_UUID_TO_UPDATE,
                    "taskTitle": "Task actualizada",
                    "taskStatus": TaskStatusEnum::DONE,
                }),
                Users::USER_API_TOKEN_OK,
            ),

            NOK_AS_CONFLICT_BY_EXISTING_USER_NAME_MUTATION => self.get_graphql_request(
                MUTATION_CREATE_USER,
                json!({
                    "userName": Users::USER_NAME_OK,
                    "userEmail": Users::USER_EMAIL_NEW,
                    "password": Users::USER_PASSWORD_NEW,
                }),
                "",
            ),

            NOK_AS_BAD_REQUEST_BY_INVALID_USER_NAME_AND_SHORT_PASSWORD_MUTATION => self.get_graphql_request(
                MUTATION_CREATE_USER,
                json!({
                    "userName": Users::USER_NAME_INVALID,
                    "userEmail": Users::USER_EMAIL_NEW,
                    "password": "short",
                }),
                "",
            ),

            NOK_AS_BAD_REQUEST_BY_EMPTY_USER_NAME_UPDATE_USER_MUTATION => self.get_graphql_request(
                MUTATION_UPDATE_USER,
                json!({
                    "userUuid": Users::USER_UUID_TO_UPDATE,
                    "userName": "  ",
                    "userEmail": Users::USER_EMAIL_UPDATED,
                }),
                Users::USER_API_TOKEN_TO_UPDATE,
            ),

            NOK_AS_CONFLICT_BY_EMAIL_OF_OTHER_USER_UPDATE_USER_MUTATION => self.get_graphql_request(
                MUTATION_UPDATE_USER,
                json!({
                    "userUuid": Users::USER_UUID_TO_UPDATE,
                    "userName": Users::USER_NAME_UPDATED,
                    "userEmail": Users::USER_EMAIL_OK,
                }),
                Users::USER_API_TOKEN_TO_UPDATE,
            ),

            NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_UPDATE_USER_MUTATION => self.get_graphql_request(
                MUTATION_UPDATE_USER,
                json!({
                    "userUuid": Users::USER_UUID_TO_UPDATE,
                    "userName": Users::USER_NAME_OTHER,
                    "userEmail": Users::USER_EMAIL_NEW,
                }),
                Users::USER_API_TOKEN_OK,
            ),

            NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_REGENERATE_API_TOKEN_MUTATION => self.get_graphql_request(
                MUTATION_REGENERATE_API_TOKEN,
                json!({"userUuid": Users::USER_UUID_TO_REGENERATE}),
                Users::USER_API_TOKEN_OK,
            ),

            NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID => self.get_graphql_request(
                "query($userUuid: ID!) { user(user_uuid: $userUuid) { user_uuid } }",
                json!({"userUuid": Users::USER_UUID_TO_UPDATE}),
                Users::USER_API_TOKEN_OK,
            ),

            NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_USER_TASKS_QUERY => self.get_graphql_request(
                "query($userUuid: ID!) { userTasks(user_uuid: $userUuid) { total_items } }",
                json!({"userUuid": Users::USER_UUID_TO_UPDATE}),
                Users::USER_API_TOKEN_OK,
            ),

            NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID => self.get_graphql_request(
                "query($taskUuid: ID!) { task(task_uuid: $taskUuid) { task_uuid } }",
                json!({"taskUuid": Tasks::TASK_UUID_NOK}),
                Users::USER_API_TOKEN_OK,
            ),

            NOK_AS_BAD_REQUEST_BY_INVALID_TARGET_DATE_MUTATION => self.get_graphql_request(
                r#"mutation($targetDate: String) { createTask(task_title: "Task GraphQL", target_date: $targetDate) { task_uuid } }"#,
                json!({"targetDate": Tasks::TARGET_DATE_INVALID}),
                Users::USER_API_TOKEN_OK,
            ),

            NOK_AS_BAD_REQUEST_BY_INVALID_TASK_TYPE_UPDATE_TASK_MUTATION => self.get_graphql_request(
                MUTATION_UPDATE_TASK,
                json!({
                    "taskUuid": Tasks::TASK_UUID_TO_UPDATE,
                    "taskTitle": "Task actualizada",
                    "taskType": Tasks::TASK_TYPE_INVALID,
                }),
                Users::USER_API_TOKEN_OK,
            ),

            NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER_UPDATE_TASK_MUTATION => self.get_graphql_request(
                MUTATION_UPDATE_TASK,
                json!({"taskUuid": Tasks::TASK_UUID_TO_UPDATE, "taskTitle": "Task de otro"}),
                Users::USER_API_TOKEN_TO_UPDATE,
            ),

            NOK_AS_UNAUTHORIZED_BY_INVALID_TOKEN => {
                self.get_graphql_request("{ users { user_uuid } }", json!({}), Users::USER_API_TOKEN_NOK)
            }
        }
    }

    /// las busquedas van con el dueño de las tasks de busqueda: sus totales no cambian por otros tests.
    fn get_search_request(&self, query: &str, variables: Value) -> Arc<HttpRequester> {
        self.get_graphql_request(query, variables, Users::USER_API_TOKEN_SEARCH)
    }

    /// bearer_token "" para probar sin autenticar.
    fn get_graphql_request(&self, query: &str, variables: Value, bearer_token: &str) -> Arc<HttpRequester> {
        let (http_requester, _) = HttpRequestMock::get_instance()
            .set_bearer_token(bearer_token)
            .set_post_raw_params(get_primitives(json!({"query": query, "variables": variables})))
            .get_mocked_instance();
        http_requester
    }
}
