package graphqlcontroller

import (
	"os"
	"slices"
	"testing"

	"task-go/src/modules/shared/domain/enums"
	taskentities "task-go/src/modules/tasks/domain/entities"
	taskenums "task-go/src/modules/tasks/domain/enums"
	"task-go/tests"
	"task-go/tests/modules/graphql/infrastructure/controllers/graphqlcontroller/mothers"
	graphqlrepositories "task-go/tests/modules/graphql/infrastructure/repositories"
	"task-go/tests/modules/shared/infrastructure/repositories"
)

// GraphQL responde 200 tambien con errores de dominio: el codigo HTTP equivalente va en errors[].extensions.code

func TestMain(m *testing.M) {
	os.Exit(tests.RunWithTestDb(m, "graphql"))
}

func TestOkAsSuccessByUsersQuery(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_USERS_QUERY)

	tests.AssertEmpty(t, graphqlResponseRepositoryMock.GetErrors())
	tests.AssertContains(t, any(repositories.UsersRawRepositoryMock.USER_UUID_OK), graphqlrepositories.GetColumn(graphqlResponseRepositoryMock.GetData()["users"], "user_uuid"))
}

func TestOkAsSuccessByUserWithTasksQuery(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_USER_WITH_TASKS_QUERY)

	tests.AssertEmpty(t, graphqlResponseRepositoryMock.GetErrors())
	user := graphqlResponseRepositoryMock.GetData()["user"].(map[string]any)
	taskPage := user["tasks"].(map[string]any)
	tests.AssertContains(t, any(repositories.TasksRawRepositoryMock.TASK_UUID_OK), graphqlrepositories.GetColumn(taskPage["items"], "task_uuid"))
}

func TestOkAsSuccessBySearchWithoutFiltersNewestFirst(t *testing.T) {
	taskPage := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_SEARCH_WITHOUT_FILTERS_NEWEST_FIRST).GetData()["tasks"].(map[string]any)

	tests.AssertEquals(t,
		[]any{repositories.TasksRawRepositoryMock.SEARCH_TITLE_GAMMA, repositories.TasksRawRepositoryMock.SEARCH_TITLE_BETA, repositories.TasksRawRepositoryMock.SEARCH_TITLE_ALPHA},
		graphqlrepositories.GetColumn(taskPage["items"], "task_title"),
	)
	taskIds := graphqlrepositories.GetColumn(taskPage["items"], "task_id")
	taskIdsDesc := slices.Clone(taskIds)
	slices.SortFunc(taskIdsDesc, func(a, b any) int { return int(b.(float64) - a.(float64)) })
	tests.AssertEquals(t, taskIdsDesc, taskIds)
	tests.AssertEquals(t, []any{1.0, 20.0, 3.0, 1.0}, []any{taskPage["page"], taskPage["page_size"], taskPage["total_items"], taskPage["total_pages"]})
}

func TestOkAsSuccessBySearchSecondPage(t *testing.T) {
	taskPage := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_SEARCH_SECOND_PAGE).GetData()["tasks"].(map[string]any)

	tests.AssertEquals(t, []any{repositories.TasksRawRepositoryMock.SEARCH_TITLE_ALPHA}, graphqlrepositories.GetColumn(taskPage["items"], "task_title"))
	tests.AssertEquals(t, []any{2.0, 3.0, 2.0}, []any{taskPage["page"], taskPage["total_items"], taskPage["total_pages"]})
}

func TestOkAsSuccessBySearchFilteredByTextAndStatus(t *testing.T) {
	taskPage := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TEXT_AND_STATUS).GetData()["tasks"].(map[string]any)

	tests.AssertEquals(t,
		[]any{repositories.TasksRawRepositoryMock.SEARCH_TITLE_GAMMA, repositories.TasksRawRepositoryMock.SEARCH_TITLE_ALPHA},
		graphqlrepositories.GetColumn(taskPage["items"], "task_title"),
	)
	tests.AssertEquals(t, 2.0, taskPage["total_items"])
}

func TestOkAsSuccessBySearchFilteredByTargetDateRange(t *testing.T) {
	taskPage := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TARGET_DATE_RANGE).GetData()["tasks"].(map[string]any)

	tests.AssertEquals(t, []any{repositories.TasksRawRepositoryMock.SEARCH_TITLE_ALPHA}, graphqlrepositories.GetColumn(taskPage["items"], "task_title"))
}

func TestOkAsSuccessBySearchTextWithLikeWildcard(t *testing.T) {
	taskPage := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_SEARCH_TEXT_WITH_LIKE_WILDCARD).GetData()["tasks"].(map[string]any)

	// "%" se busca literal: solo la descripcion que lo contiene, no todas
	tests.AssertEquals(t, []any{repositories.TasksRawRepositoryMock.SEARCH_TITLE_GAMMA}, graphqlrepositories.GetColumn(taskPage["items"], "task_title"))
}

func TestNokAsBadRequestByInvalidPagination(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_BAD_REQUEST_BY_INVALID_PAGINATION)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.GetFirstErrorCode())
	// los dos campos erroneos viajan en el mensaje de la FieldsException
	errorDetails := graphqlResponseRepositoryMock.GetFirstErrorDetails()
	tests.AssertEquals(t, 1, len(errorDetails))
	tests.AssertStringContainsString(t, "page:", errorDetails[0])
	tests.AssertStringContainsString(t, "page_size:", errorDetails[0])
}

func TestOkAsSuccessByTaskQueryWithVariables(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_TASK_QUERY_WITH_VARIABLES)

	tests.AssertEmpty(t, graphqlResponseRepositoryMock.GetErrors())
	task := graphqlResponseRepositoryMock.GetData()["task"].(map[string]any)
	tests.AssertEquals(t, any(repositories.TasksRawRepositoryMock.TASK_TITLE_OK), task["task_title"])
}

func TestOkAsSuccessByCreateUserMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_CREATE_USER_MUTATION)

	tests.AssertEmpty(t, graphqlResponseRepositoryMock.GetErrors())
	createdUser := graphqlResponseRepositoryMock.GetData()["createUser"].(map[string]any)
	tests.AssertEquals(t, any(repositories.UsersRawRepositoryMock.USER_EMAIL_NEW_GRAPHQL), createdUser["user_email"])
}

func TestOkAsSuccessByCreateTaskMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_CREATE_TASK_MUTATION)

	tests.AssertEmpty(t, graphqlResponseRepositoryMock.GetErrors())
	createdTask := graphqlResponseRepositoryMock.GetData()["createTask"].(map[string]any)
	tests.AssertEquals(t, any(repositories.UsersRawRepositoryMock.USER_UUID_OK), createdTask["user_uuid"])
	tests.AssertStringStartsWith(t, taskentities.TASK_UUID_PREFIX, createdTask["task_uuid"].(string))
	tests.AssertEquals(t, any(taskenums.TaskStatusEnum.DEFAULT), createdTask["task_status"])
	tests.AssertEquals(t, any(taskenums.TaskTypeEnum.DEFAULT), createdTask["task_type"])
}

func TestOkAsSuccessByDeleteTaskMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_DELETE_TASK_MUTATION)

	tests.AssertEmpty(t, graphqlResponseRepositoryMock.GetErrors())
	deleteTaskResult := graphqlResponseRepositoryMock.GetData()["deleteTask"].(map[string]any)
	tests.AssertEquals(t, 1.0, deleteTaskResult["affected"])
}

func TestOkAsSuccessByCreateUserMutationWithoutToken(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_CREATE_USER_MUTATION_WITHOUT_TOKEN)

	tests.AssertEmpty(t, graphqlResponseRepositoryMock.GetErrors())
	createdUser := graphqlResponseRepositoryMock.GetData()["createUser"].(map[string]any)
	tests.AssertNotEmpty(t, createdUser["api_token"])
}

func TestOkAsSuccessByLogInMutationWithoutToken(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_LOG_IN_MUTATION_WITHOUT_TOKEN)

	tests.AssertEmpty(t, graphqlResponseRepositoryMock.GetErrors())
	loggedInUser := graphqlResponseRepositoryMock.GetData()["logIn"].(map[string]any)
	tests.AssertEquals(t, any(repositories.UsersRawRepositoryMock.USER_UUID_TO_LOG_IN), loggedInUser["user_uuid"])
	// token nuevo: el de los fixtures deja de valer
	tests.AssertNotEquals(t, any(repositories.UsersRawRepositoryMock.USER_API_TOKEN_TO_LOG_IN), loggedInUser["api_token"])
}

func TestNokAsUnauthorizedByWrongPasswordLogInMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD_LOG_IN_MUTATION)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_UNAUTHORIZED, graphqlResponseRepositoryMock.GetFirstErrorCode())
	tests.AssertNil(t, graphqlResponseRepositoryMock.GetData()["logIn"])
}

func TestNokAsUnauthorizedByMissingToken(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_UNAUTHORIZED, graphqlResponseRepositoryMock.GetFirstErrorCode())
}

func TestNokAsNotFoundByTaskOfOtherUser(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.GetFirstErrorCode())
	tests.AssertNil(t, graphqlResponseRepositoryMock.GetData()["task"])
}

func TestNokAsNotFoundByFakeUserUuid(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.GetFirstErrorCode())
	tests.AssertNil(t, graphqlResponseRepositoryMock.GetData()["user"])
}

func TestNokAsBadRequestByInvalidEmailMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_BAD_REQUEST_BY_INVALID_EMAIL_MUTATION)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.GetFirstErrorCode())
	tests.AssertNotEmpty(t, graphqlResponseRepositoryMock.GetFirstErrorDetails())
}

func TestNokAsConflictByExistingEmailMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_CONFLICT_BY_EXISTING_EMAIL_MUTATION)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_CONFLICT, graphqlResponseRepositoryMock.GetFirstErrorCode())
}

func TestNokAsBadRequestByInvalidTaskStatusMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_BAD_REQUEST_BY_INVALID_TASK_STATUS_MUTATION)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.GetFirstErrorCode())
}

func TestNokAsGraphqlErrorByUnknownField(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_GRAPHQL_ERROR_BY_UNKNOWN_FIELD)

	tests.AssertNotEmpty(t, graphqlResponseRepositoryMock.GetErrors())
	tests.AssertEquals(t, 0, graphqlResponseRepositoryMock.GetFirstErrorCode())
}

func TestOkAsSuccessByUpdateUserMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_UPDATE_USER_MUTATION)

	tests.AssertEmpty(t, graphqlResponseRepositoryMock.GetErrors())
	updatedUser := graphqlResponseRepositoryMock.GetData()["updateUser"].(map[string]any)
	tests.AssertEquals(t, any(repositories.UsersRawRepositoryMock.USER_NAME_UPDATED), updatedUser["user_name"])
	tests.AssertEquals(t, any(repositories.UsersRawRepositoryMock.USER_EMAIL_UPDATED), updatedUser["user_email"])
}

func TestOkAsSuccessByDeleteUserMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_DELETE_USER_MUTATION)

	tests.AssertEmpty(t, graphqlResponseRepositoryMock.GetErrors())
	deleteUserResult := graphqlResponseRepositoryMock.GetData()["deleteUser"].(map[string]any)
	tests.AssertEquals(t, 1.0, deleteUserResult["affected"])
}

func TestOkAsSuccessByRegenerateApiTokenMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_REGENERATE_API_TOKEN_MUTATION)

	tests.AssertEmpty(t, graphqlResponseRepositoryMock.GetErrors())
	newApiToken := graphqlResponseRepositoryMock.GetData()["regenerateApiToken"].(map[string]any)["api_token"]
	tests.AssertNotEmpty(t, newApiToken)
	tests.AssertNotEquals(t, any(repositories.UsersRawRepositoryMock.USER_API_TOKEN_TO_REGENERATE), newApiToken)
}

func TestOkAsSuccessByUpdateTaskMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.OK_AS_SUCCESS_BY_UPDATE_TASK_MUTATION)

	tests.AssertEmpty(t, graphqlResponseRepositoryMock.GetErrors())
	updatedTask := graphqlResponseRepositoryMock.GetData()["updateTask"].(map[string]any)
	tests.AssertEquals(t, any(taskenums.TaskStatusEnum.DONE), updatedTask["task_status"])
}

func TestNokAsConflictByExistingUserNameMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_CONFLICT_BY_EXISTING_USER_NAME_MUTATION)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_CONFLICT, graphqlResponseRepositoryMock.GetFirstErrorCode())
}

func TestNokAsBadRequestByInvalidUserNameAndShortPasswordMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_BAD_REQUEST_BY_INVALID_USER_NAME_AND_SHORT_PASSWORD_MUTATION)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.GetFirstErrorCode())
	errorDetails := graphqlResponseRepositoryMock.GetFirstErrorDetails()
	tests.AssertEquals(t, 1, len(errorDetails))
	tests.AssertStringContainsString(t, "user_name:", errorDetails[0])
	tests.AssertStringContainsString(t, "password:", errorDetails[0])
}

func TestNokAsBadRequestByEmptyUserNameUpdateUserMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_BAD_REQUEST_BY_EMPTY_USER_NAME_UPDATE_USER_MUTATION)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.GetFirstErrorCode())
}

func TestNokAsConflictByEmailOfOtherUserUpdateUserMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_CONFLICT_BY_EMAIL_OF_OTHER_USER_UPDATE_USER_MUTATION)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_CONFLICT, graphqlResponseRepositoryMock.GetFirstErrorCode())
}

func TestNokAsNotFoundByOtherUserUuidUpdateUserMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_UPDATE_USER_MUTATION)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.GetFirstErrorCode())
	tests.AssertNil(t, graphqlResponseRepositoryMock.GetData()["updateUser"])
}

func TestNokAsNotFoundByOtherUserUuidRegenerateApiTokenMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_REGENERATE_API_TOKEN_MUTATION)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.GetFirstErrorCode())
}

func TestNokAsNotFoundByOtherUserUuid(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.GetFirstErrorCode())
	tests.AssertNil(t, graphqlResponseRepositoryMock.GetData()["user"])
}

func TestNokAsNotFoundByOtherUserUuidUserTasksQuery(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_USER_TASKS_QUERY)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.GetFirstErrorCode())
}

func TestNokAsNotFoundByFakeTaskUuid(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.GetFirstErrorCode())
	tests.AssertNil(t, graphqlResponseRepositoryMock.GetData()["task"])
}

func TestNokAsBadRequestByInvalidTargetDateMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_BAD_REQUEST_BY_INVALID_TARGET_DATE_MUTATION)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.GetFirstErrorCode())
}

func TestNokAsBadRequestByInvalidTaskTypeUpdateTaskMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_BAD_REQUEST_BY_INVALID_TASK_TYPE_UPDATE_TASK_MUTATION)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.GetFirstErrorCode())
}

func TestNokAsNotFoundByTaskOfOtherUserUpdateTaskMutation(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER_UPDATE_TASK_MUTATION)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.GetFirstErrorCode())
	tests.AssertNil(t, graphqlResponseRepositoryMock.GetData()["updateTask"])
}

func TestNokAsUnauthorizedByInvalidToken(t *testing.T) {
	graphqlResponseRepositoryMock := getGraphqlResponseByTestCase(t, mothers.NOK_AS_UNAUTHORIZED_BY_INVALID_TOKEN)

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_UNAUTHORIZED, graphqlResponseRepositoryMock.GetFirstErrorCode())
}

func TestNokAsBadRequestByEmptyQuery(t *testing.T) {
	httpRequester := mothers.GraphqlRequestMotherGetInstance().GetRequestByTestCase(mothers.NOK_AS_BAD_REQUEST_BY_EMPTY_QUERY)

	httpResponse := mothers.GetControllerInstance(httpRequester).Execute()

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_BAD_REQUEST, httpResponse.GetStatusCode())
}

// getGraphqlResponseByTestCase toda peticion GraphQL que se ejecuta responde 200; lo que cambia es data / errors.
func getGraphqlResponseByTestCase(t *testing.T, graphqlTestCaseEnum mothers.GraphqlTestCaseEnum) *graphqlrepositories.GraphqlResponseRepositoryMock {
	t.Helper()
	httpRequester := mothers.GraphqlRequestMotherGetInstance().GetRequestByTestCase(graphqlTestCaseEnum)

	httpResponse := mothers.GetControllerInstance(httpRequester).Execute()

	tests.AssertEquals(t, enums.ExceptionEnum.CODE_OK, httpResponse.GetStatusCode(), string(graphqlTestCaseEnum))
	return graphqlrepositories.GraphqlResponseRepositoryMockFromHttpResponse(httpResponse)
}
