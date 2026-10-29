package mothers

import (
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/tasks/domain/enums"
	"task-go/tests/modules/shared/infrastructure/components"
	"task-go/tests/modules/shared/infrastructure/repositories"
)

const (
	MUTATION_CREATE_USER          = "mutation($userName: String!, $userEmail: String!, $password: String!) { createUser(user_name: $userName, user_email: $userEmail, password: $password) { user_uuid } }"
	MUTATION_UPDATE_USER          = "mutation($userUuid: ID!, $userName: String!, $userEmail: String!) { updateUser(user_uuid: $userUuid, user_name: $userName, user_email: $userEmail) { user_uuid user_name user_email } }"
	MUTATION_REGENERATE_API_TOKEN = "mutation($userUuid: ID!) { regenerateApiToken(user_uuid: $userUuid) { user_uuid api_token } }"
	MUTATION_UPDATE_TASK          = "mutation($taskUuid: ID!, $taskTitle: String!, $taskStatus: String, $taskType: String) { updateTask(task_uuid: $taskUuid, task_title: $taskTitle, task_status: $taskStatus, task_type: $taskType) { task_uuid task_title task_status } }"
)

type GraphqlRequestMother struct{}

func GraphqlRequestMotherGetInstance() *GraphqlRequestMother {
	return &GraphqlRequestMother{}
}

// GetRequestByTestCase el HttpRequester de la peticion (GraphqlController.Execute() no tiene argumentos: todo va en
// el body).
func (m *GraphqlRequestMother) GetRequestByTestCase(useCase GraphqlTestCaseEnum) *httprequester.HttpRequester {
	users := repositories.UsersRawRepositoryMock
	tasks := repositories.TasksRawRepositoryMock
	switch useCase {
	case OK_AS_SUCCESS_BY_USERS_QUERY:
		return m.getGraphqlRequest("{ users { user_uuid user_name user_email } }", nil, users.USER_API_TOKEN_OK)

	case OK_AS_SUCCESS_BY_USER_WITH_TASKS_QUERY:
		return m.getGraphqlRequest(`{ user(user_uuid: "`+users.USER_UUID_OK+`") { user_uuid tasks { items { task_uuid task_title } total_items } } }`, nil, users.USER_API_TOKEN_OK)

	case OK_AS_SUCCESS_BY_SEARCH_WITHOUT_FILTERS_NEWEST_FIRST:
		return m.getSearchRequest("{ tasks { items { task_id task_title } page page_size total_items total_pages } }", nil)

	case OK_AS_SUCCESS_BY_SEARCH_SECOND_PAGE:
		return m.getSearchRequest("{ tasks(page: 2, page_size: 2) { items { task_title } page total_items total_pages } }", nil)

	case OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TEXT_AND_STATUS:
		return m.getSearchRequest(
			"query($filter: TasksFilter) { tasks(filter: $filter) { items { task_title } total_items } }",
			map[string]any{"filter": map[string]any{"task_text": "SEARCH", "task_status": enums.TaskStatusEnum.PENDING}},
		)

	case OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TARGET_DATE_RANGE:
		return m.getSearchRequest(
			"query($filter: TasksFilter) { tasks(filter: $filter) { items { task_title } total_items } }",
			map[string]any{"filter": map[string]any{"target_date_from": "2026-01-01", "target_date_to": "2026-04-01"}},
		)

	case OK_AS_SUCCESS_BY_SEARCH_TEXT_WITH_LIKE_WILDCARD:
		return m.getSearchRequest(`{ tasks(filter: {task_text: "%"}) { items { task_title } total_items } }`, nil)

	case NOK_AS_BAD_REQUEST_BY_INVALID_PAGINATION:
		return m.getSearchRequest("{ tasks(page: 0, page_size: 101) { total_items } }", nil)

	case OK_AS_SUCCESS_BY_TASK_QUERY_WITH_VARIABLES:
		return m.getGraphqlRequest(
			"query GetTask($taskUuid: ID!) { task(task_uuid: $taskUuid) { task_uuid task_title } }",
			map[string]any{"taskUuid": tasks.TASK_UUID_OK},
			users.USER_API_TOKEN_OK,
		)

	case OK_AS_SUCCESS_BY_CREATE_USER_MUTATION:
		return m.getGraphqlRequest(
			"mutation($userName: String!, $userEmail: String!, $password: String!) { createUser(user_name: $userName, user_email: $userEmail, password: $password) { user_uuid user_email } }",
			map[string]any{
				"userName":  users.USER_NAME_NEW_GRAPHQL,
				"userEmail": users.USER_EMAIL_NEW_GRAPHQL,
				"password":  users.USER_PASSWORD_NEW,
			},
			users.USER_API_TOKEN_OK,
		)

	case OK_AS_SUCCESS_BY_CREATE_TASK_MUTATION:
		return m.getGraphqlRequest(`mutation { createTask(task_title: "Task GraphQL") { task_uuid user_uuid task_status task_type } }`, nil, users.USER_API_TOKEN_OK)

	case OK_AS_SUCCESS_BY_DELETE_TASK_MUTATION:
		return m.getGraphqlRequest(
			"mutation($taskUuid: ID!) { deleteTask(task_uuid: $taskUuid) { task_uuid affected } }",
			map[string]any{"taskUuid": tasks.TASK_UUID_TO_DELETE_GRAPHQL},
			users.USER_API_TOKEN_OK,
		)

	case NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID:
		return m.getGraphqlRequest(
			"query($userUuid: ID!) { user(user_uuid: $userUuid) { user_uuid } }",
			map[string]any{"userUuid": users.USER_UUID_NOK},
			users.USER_API_TOKEN_OK,
		)

	case NOK_AS_BAD_REQUEST_BY_INVALID_EMAIL_MUTATION:
		return m.getGraphqlRequest(MUTATION_CREATE_USER, map[string]any{
			"userName":  users.USER_NAME_OTHER,
			"userEmail": users.USER_EMAIL_INVALID,
			"password":  users.USER_PASSWORD_NEW,
		}, users.USER_API_TOKEN_OK)

	case NOK_AS_CONFLICT_BY_EXISTING_EMAIL_MUTATION:
		return m.getGraphqlRequest(MUTATION_CREATE_USER, map[string]any{
			"userName":  users.USER_NAME_OTHER,
			"userEmail": users.USER_EMAIL_OK,
			"password":  users.USER_PASSWORD_NEW,
		}, users.USER_API_TOKEN_OK)

	case NOK_AS_BAD_REQUEST_BY_INVALID_TASK_STATUS_MUTATION:
		return m.getGraphqlRequest(
			`mutation($taskStatus: String) { createTask(task_title: "Task GraphQL", task_status: $taskStatus) { task_uuid } }`,
			map[string]any{"taskStatus": tasks.TASK_STATUS_INVALID},
			users.USER_API_TOKEN_OK,
		)

	case OK_AS_SUCCESS_BY_CREATE_USER_MUTATION_WITHOUT_TOKEN:
		return m.getGraphqlRequest(
			"mutation($userName: String!, $userEmail: String!, $password: String!) { createUser(user_name: $userName, user_email: $userEmail, password: $password) { user_uuid api_token } }",
			map[string]any{
				"userName":  users.USER_NAME_NEW_GRAPHQL_WITHOUT_TOKEN,
				"userEmail": users.USER_EMAIL_NEW_GRAPHQL_WITHOUT_TOKEN,
				"password":  users.USER_PASSWORD_NEW,
			},
			"",
		)

	case OK_AS_SUCCESS_BY_LOG_IN_MUTATION_WITHOUT_TOKEN:
		return m.getGraphqlRequest(
			"mutation($userName: String!, $password: String!) { logIn(user_name: $userName, password: $password) { user_uuid api_token } }",
			map[string]any{"userName": users.USER_NAME_TO_LOG_IN, "password": users.USER_PASSWORD_TO_LOG_IN},
			"",
		)

	case NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD_LOG_IN_MUTATION:
		return m.getGraphqlRequest(
			"mutation($userName: String!, $password: String!) { logIn(user_name: $userName, password: $password) { user_uuid } }",
			map[string]any{"userName": users.USER_NAME_TO_LOG_IN, "password": users.USER_PASSWORD_NOK},
			"",
		)

	case NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN:
		return m.getGraphqlRequest("{ users { user_uuid } }", nil, "")

	case NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER:
		return m.getGraphqlRequest(
			"query($taskUuid: ID!) { task(task_uuid: $taskUuid) { task_uuid } }",
			map[string]any{"taskUuid": tasks.TASK_UUID_OK},
			users.USER_API_TOKEN_TO_UPDATE,
		)

	case NOK_AS_GRAPHQL_ERROR_BY_UNKNOWN_FIELD:
		return m.getGraphqlRequest("{ users { unknown_field } }", nil, users.USER_API_TOKEN_OK)

	case NOK_AS_BAD_REQUEST_BY_EMPTY_QUERY:
		return m.getGraphqlRequest("   ", nil, users.USER_API_TOKEN_OK)

	case OK_AS_SUCCESS_BY_UPDATE_USER_MUTATION:
		return m.getGraphqlRequest(MUTATION_UPDATE_USER, map[string]any{
			"userUuid":  users.USER_UUID_TO_UPDATE,
			"userName":  users.USER_NAME_UPDATED,
			"userEmail": users.USER_EMAIL_UPDATED,
		}, users.USER_API_TOKEN_TO_UPDATE)

	case OK_AS_SUCCESS_BY_DELETE_USER_MUTATION:
		return m.getGraphqlRequest(
			"mutation($userUuid: ID!) { deleteUser(user_uuid: $userUuid) { user_uuid affected } }",
			map[string]any{"userUuid": users.USER_UUID_TO_DELETE},
			users.USER_API_TOKEN_TO_DELETE,
		)

	case OK_AS_SUCCESS_BY_REGENERATE_API_TOKEN_MUTATION:
		return m.getGraphqlRequest(MUTATION_REGENERATE_API_TOKEN, map[string]any{
			"userUuid": users.USER_UUID_TO_REGENERATE,
		}, users.USER_API_TOKEN_TO_REGENERATE)

	case OK_AS_SUCCESS_BY_UPDATE_TASK_MUTATION:
		return m.getGraphqlRequest(MUTATION_UPDATE_TASK, map[string]any{
			"taskUuid":   tasks.TASK_UUID_TO_UPDATE,
			"taskTitle":  "Task actualizada",
			"taskStatus": enums.TaskStatusEnum.DONE,
		}, users.USER_API_TOKEN_OK)

	case NOK_AS_CONFLICT_BY_EXISTING_USER_NAME_MUTATION:
		return m.getGraphqlRequest(MUTATION_CREATE_USER, map[string]any{
			"userName":  users.USER_NAME_OK,
			"userEmail": users.USER_EMAIL_NEW,
			"password":  users.USER_PASSWORD_NEW,
		}, "")

	case NOK_AS_BAD_REQUEST_BY_INVALID_USER_NAME_AND_SHORT_PASSWORD_MUTATION:
		return m.getGraphqlRequest(MUTATION_CREATE_USER, map[string]any{
			"userName":  users.USER_NAME_INVALID,
			"userEmail": users.USER_EMAIL_NEW,
			"password":  "short",
		}, "")

	case NOK_AS_BAD_REQUEST_BY_EMPTY_USER_NAME_UPDATE_USER_MUTATION:
		return m.getGraphqlRequest(MUTATION_UPDATE_USER, map[string]any{
			"userUuid":  users.USER_UUID_TO_UPDATE,
			"userName":  "  ",
			"userEmail": users.USER_EMAIL_UPDATED,
		}, users.USER_API_TOKEN_TO_UPDATE)

	case NOK_AS_CONFLICT_BY_EMAIL_OF_OTHER_USER_UPDATE_USER_MUTATION:
		return m.getGraphqlRequest(MUTATION_UPDATE_USER, map[string]any{
			"userUuid":  users.USER_UUID_TO_UPDATE,
			"userName":  users.USER_NAME_UPDATED,
			"userEmail": users.USER_EMAIL_OK,
		}, users.USER_API_TOKEN_TO_UPDATE)

	case NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_UPDATE_USER_MUTATION:
		return m.getGraphqlRequest(MUTATION_UPDATE_USER, map[string]any{
			"userUuid":  users.USER_UUID_TO_UPDATE,
			"userName":  users.USER_NAME_OTHER,
			"userEmail": users.USER_EMAIL_NEW,
		}, users.USER_API_TOKEN_OK)

	case NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_REGENERATE_API_TOKEN_MUTATION:
		return m.getGraphqlRequest(MUTATION_REGENERATE_API_TOKEN, map[string]any{
			"userUuid": users.USER_UUID_TO_REGENERATE,
		}, users.USER_API_TOKEN_OK)

	case NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID:
		return m.getGraphqlRequest(
			"query($userUuid: ID!) { user(user_uuid: $userUuid) { user_uuid } }",
			map[string]any{"userUuid": users.USER_UUID_TO_UPDATE},
			users.USER_API_TOKEN_OK,
		)

	case NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_USER_TASKS_QUERY:
		return m.getGraphqlRequest(
			"query($userUuid: ID!) { userTasks(user_uuid: $userUuid) { total_items } }",
			map[string]any{"userUuid": users.USER_UUID_TO_UPDATE},
			users.USER_API_TOKEN_OK,
		)

	case NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID:
		return m.getGraphqlRequest(
			"query($taskUuid: ID!) { task(task_uuid: $taskUuid) { task_uuid } }",
			map[string]any{"taskUuid": tasks.TASK_UUID_NOK},
			users.USER_API_TOKEN_OK,
		)

	case NOK_AS_BAD_REQUEST_BY_INVALID_TARGET_DATE_MUTATION:
		return m.getGraphqlRequest(
			`mutation($targetDate: String) { createTask(task_title: "Task GraphQL", target_date: $targetDate) { task_uuid } }`,
			map[string]any{"targetDate": tasks.TARGET_DATE_INVALID},
			users.USER_API_TOKEN_OK,
		)

	case NOK_AS_BAD_REQUEST_BY_INVALID_TASK_TYPE_UPDATE_TASK_MUTATION:
		return m.getGraphqlRequest(MUTATION_UPDATE_TASK, map[string]any{
			"taskUuid":  tasks.TASK_UUID_TO_UPDATE,
			"taskTitle": "Task actualizada",
			"taskType":  tasks.TASK_TYPE_INVALID,
		}, users.USER_API_TOKEN_OK)

	case NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER_UPDATE_TASK_MUTATION:
		return m.getGraphqlRequest(MUTATION_UPDATE_TASK, map[string]any{
			"taskUuid":  tasks.TASK_UUID_TO_UPDATE,
			"taskTitle": "Task de otro",
		}, users.USER_API_TOKEN_TO_UPDATE)

	case NOK_AS_UNAUTHORIZED_BY_INVALID_TOKEN:
		return m.getGraphqlRequest("{ users { user_uuid } }", nil, users.USER_API_TOKEN_NOK)
	}
	panic("unknown test case " + string(useCase))
}

// getSearchRequest las busquedas van con el dueño de las tasks de busqueda: sus totales no cambian por otros tests.
func (m *GraphqlRequestMother) getSearchRequest(query string, variables map[string]any) *httprequester.HttpRequester {
	return m.getGraphqlRequest(query, variables, repositories.UsersRawRepositoryMock.USER_API_TOKEN_SEARCH)
}

// getGraphqlRequest bearerToken "" para probar sin autenticar.
func (m *GraphqlRequestMother) getGraphqlRequest(query string, variables map[string]any, bearerToken string) *httprequester.HttpRequester {
	if variables == nil {
		variables = map[string]any{}
	}
	httpRequester, _ := components.GetInstance().
		SetBearerToken(bearerToken).
		SetPostRawParams(map[string]any{"query": query, "variables": variables}).
		GetMockedInstance()
	return httpRequester
}
