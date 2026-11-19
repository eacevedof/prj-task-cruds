from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum
from Modules.Shared.Infrastructure.Components.HttpResponder.HttpResponder import HttpResponder
from Modules.Tasks.Domain.Entities.TaskEntity import TaskEntity
from Modules.Tasks.Domain.Enums.TaskStatusEnum import TaskStatusEnum
from Modules.Tasks.Domain.Enums.TaskTypeEnum import TaskTypeEnum

from tests.AbstractTestCase import AbstractTestCase
from tests.bootstrap import runWithTestDb
from tests.Modules.GraphQl.Infrastructure.Controllers.GraphQlController.Mothers.GraphQlControllerMother import GraphQlControllerMother
from tests.Modules.GraphQl.Infrastructure.Controllers.GraphQlController.Mothers.GraphQlRequestMother import GraphQlRequestMother
from tests.Modules.GraphQl.Infrastructure.Controllers.GraphQlController.Mothers.GraphQlTestCaseEnum import GraphQlTestCaseEnum
from tests.Modules.GraphQl.Infrastructure.Repositories.GraphQlResponseRepositoryMock import GraphQlResponseRepositoryMock
from tests.Modules.Shared.Infrastructure.Repositories.TasksRawRepositoryMock import TasksRawRepositoryMock
from tests.Modules.Shared.Infrastructure.Repositories.UsersRawRepositoryMock import UsersRawRepositoryMock


class GraphQlControllerTest(AbstractTestCase):
    """
    GraphQL responde 200 tambien con errores de dominio: el codigo HTTP equivalente va en errors[].extensions.code
    """

    @classmethod
    def setUpClass(cls) -> None:
        runWithTestDb("graphql")

    def testOkAsSuccessByUsersQuery(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_USERS_QUERY)

        self.assertEmpty(graphqlResponseRepositoryMock.getErrors())
        self.assertContains(
            UsersRawRepositoryMock.USER_UUID_OK,
            [user["user_uuid"] for user in graphqlResponseRepositoryMock.getData()["users"]]
        )

    def testOkAsSuccessByUserWithTasksQuery(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_USER_WITH_TASKS_QUERY)

        self.assertEmpty(graphqlResponseRepositoryMock.getErrors())
        self.assertContains(
            TasksRawRepositoryMock.TASK_UUID_OK,
            [task["task_uuid"] for task in graphqlResponseRepositoryMock.getData()["user"]["tasks"]["items"]]
        )

    def testOkAsSuccessBySearchWithoutFiltersNewestFirst(self) -> None:
        taskPage = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_WITHOUT_FILTERS_NEWEST_FIRST).getData()["tasks"]

        self.assertEquals(
            [TasksRawRepositoryMock.SEARCH_TITLE_GAMMA, TasksRawRepositoryMock.SEARCH_TITLE_BETA, TasksRawRepositoryMock.SEARCH_TITLE_ALPHA],
            [task["task_title"] for task in taskPage["items"]]
        )
        taskIds = [task["task_id"] for task in taskPage["items"]]
        taskIdsDesc = sorted(taskIds, reverse=True)
        self.assertSame(taskIdsDesc, taskIds)
        self.assertEquals([1, 20, 3, 1], [taskPage["page"], taskPage["page_size"], taskPage["total_items"], taskPage["total_pages"]])

    def testOkAsSuccessBySearchSecondPage(self) -> None:
        taskPage = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_SECOND_PAGE).getData()["tasks"]

        self.assertEquals([TasksRawRepositoryMock.SEARCH_TITLE_ALPHA], [task["task_title"] for task in taskPage["items"]])
        self.assertEquals([2, 3, 2], [taskPage["page"], taskPage["total_items"], taskPage["total_pages"]])

    def testOkAsSuccessBySearchFilteredByTextAndStatus(self) -> None:
        taskPage = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TEXT_AND_STATUS).getData()["tasks"]

        self.assertEquals(
            [TasksRawRepositoryMock.SEARCH_TITLE_GAMMA, TasksRawRepositoryMock.SEARCH_TITLE_ALPHA],
            [task["task_title"] for task in taskPage["items"]]
        )
        self.assertEquals(2, taskPage["total_items"])

    def testOkAsSuccessBySearchFilteredByTargetDateRange(self) -> None:
        taskPage = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_FILTERED_BY_TARGET_DATE_RANGE).getData()["tasks"]

        self.assertEquals([TasksRawRepositoryMock.SEARCH_TITLE_ALPHA], [task["task_title"] for task in taskPage["items"]])

    def testOkAsSuccessBySearchTextWithLikeWildcard(self) -> None:
        taskPage = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_SEARCH_TEXT_WITH_LIKE_WILDCARD).getData()["tasks"]

        # "%" se busca literal: solo la descripcion que lo contiene, no todas
        self.assertEquals([TasksRawRepositoryMock.SEARCH_TITLE_GAMMA], [task["task_title"] for task in taskPage["items"]])

    def testNokAsBadRequestByInvalidPagination(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_PAGINATION)

        self.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode())
        # los dos campos erroneos viajan en el mensaje de la FieldsException
        errorDetails = graphqlResponseRepositoryMock.getFirstErrorDetails()
        self.assertCount(1, errorDetails)
        self.assertStringContainsString("page:", errorDetails[0])
        self.assertStringContainsString("page_size:", errorDetails[0])

    def testOkAsSuccessByTaskQueryWithVariables(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_TASK_QUERY_WITH_VARIABLES)

        self.assertEmpty(graphqlResponseRepositoryMock.getErrors())
        self.assertEquals(TasksRawRepositoryMock.TASK_TITLE_OK, graphqlResponseRepositoryMock.getData()["task"]["task_title"])

    def testOkAsSuccessByCreateUserMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_CREATE_USER_MUTATION)

        self.assertEmpty(graphqlResponseRepositoryMock.getErrors())
        self.assertEquals(
            UsersRawRepositoryMock.USER_EMAIL_NEW_GRAPHQL,
            graphqlResponseRepositoryMock.getData()["createUser"]["user_email"]
        )

    def testOkAsSuccessByCreateTaskMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_CREATE_TASK_MUTATION)

        self.assertEmpty(graphqlResponseRepositoryMock.getErrors())
        createdTask = graphqlResponseRepositoryMock.getData()["createTask"]
        self.assertEquals(UsersRawRepositoryMock.USER_UUID_OK, createdTask["user_uuid"])
        self.assertStringStartsWith(TaskEntity.TASK_UUID_PREFIX, createdTask["task_uuid"])
        self.assertEquals(TaskStatusEnum.DEFAULT, createdTask["task_status"])
        self.assertEquals(TaskTypeEnum.DEFAULT, createdTask["task_type"])

    def testOkAsSuccessByDeleteTaskMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_DELETE_TASK_MUTATION)

        self.assertEmpty(graphqlResponseRepositoryMock.getErrors())
        self.assertEquals(1, graphqlResponseRepositoryMock.getData()["deleteTask"]["affected"])

    def testOkAsSuccessByCreateUserMutationWithoutToken(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_CREATE_USER_MUTATION_WITHOUT_TOKEN)

        self.assertEmpty(graphqlResponseRepositoryMock.getErrors())
        self.assertNotEmpty(graphqlResponseRepositoryMock.getData()["createUser"]["api_token"])

    def testOkAsSuccessByLogInMutationWithoutToken(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_LOG_IN_MUTATION_WITHOUT_TOKEN)

        self.assertEmpty(graphqlResponseRepositoryMock.getErrors())
        loggedInUser = graphqlResponseRepositoryMock.getData()["logIn"]
        self.assertEquals(UsersRawRepositoryMock.USER_UUID_TO_LOG_IN, loggedInUser["user_uuid"])
        # token nuevo: el de los fixtures deja de valer
        self.assertNotEquals(UsersRawRepositoryMock.USER_API_TOKEN_TO_LOG_IN, loggedInUser["api_token"])

    def testNokAsUnauthorizedByWrongPasswordLogInMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD_LOG_IN_MUTATION)

        self.assertEquals(ExceptionEnum.CODE_UNAUTHORIZED, graphqlResponseRepositoryMock.getFirstErrorCode())
        self.assertNull(graphqlResponseRepositoryMock.getData().get("logIn"))

    def testNokAsUnauthorizedByMissingToken(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_MISSING_TOKEN)

        self.assertEquals(ExceptionEnum.CODE_UNAUTHORIZED, graphqlResponseRepositoryMock.getFirstErrorCode())

    def testNokAsNotFoundByTaskOfOtherUser(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER)

        self.assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode())
        self.assertNull(graphqlResponseRepositoryMock.getData().get("task"))

    def testNokAsNotFoundByFakeUserUuid(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID)

        self.assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode())
        self.assertNull(graphqlResponseRepositoryMock.getData().get("user"))

    def testNokAsBadRequestByInvalidEmailMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_EMAIL_MUTATION)

        self.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode())
        self.assertNotEmpty(graphqlResponseRepositoryMock.getFirstErrorDetails())

    def testNokAsConflictByExistingEmailMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_CONFLICT_BY_EXISTING_EMAIL_MUTATION)

        self.assertEquals(ExceptionEnum.CODE_CONFLICT, graphqlResponseRepositoryMock.getFirstErrorCode())

    def testNokAsBadRequestByInvalidTaskStatusMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_TASK_STATUS_MUTATION)

        self.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode())

    def testNokAsGraphQlErrorByUnknownField(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_GRAPHQL_ERROR_BY_UNKNOWN_FIELD)

        self.assertNotEmpty(graphqlResponseRepositoryMock.getErrors())
        self.assertEquals(0, graphqlResponseRepositoryMock.getFirstErrorCode())

    def testOkAsSuccessByUpdateUserMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_UPDATE_USER_MUTATION)

        self.assertEmpty(graphqlResponseRepositoryMock.getErrors())
        updatedUser = graphqlResponseRepositoryMock.getData()["updateUser"]
        self.assertEquals(UsersRawRepositoryMock.USER_NAME_UPDATED, updatedUser["user_name"])
        self.assertEquals(UsersRawRepositoryMock.USER_EMAIL_UPDATED, updatedUser["user_email"])

    def testOkAsSuccessByDeleteUserMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_DELETE_USER_MUTATION)

        self.assertEmpty(graphqlResponseRepositoryMock.getErrors())
        self.assertEquals(1, graphqlResponseRepositoryMock.getData()["deleteUser"]["affected"])

    def testOkAsSuccessByRegenerateApiTokenMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_REGENERATE_API_TOKEN_MUTATION)

        self.assertEmpty(graphqlResponseRepositoryMock.getErrors())
        newApiToken = graphqlResponseRepositoryMock.getData()["regenerateApiToken"]["api_token"]
        self.assertNotEmpty(newApiToken)
        self.assertNotEquals(UsersRawRepositoryMock.USER_API_TOKEN_TO_REGENERATE, newApiToken)

    def testOkAsSuccessByUpdateTaskMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.OK_AS_SUCCESS_BY_UPDATE_TASK_MUTATION)

        self.assertEmpty(graphqlResponseRepositoryMock.getErrors())
        self.assertEquals(TaskStatusEnum.DONE, graphqlResponseRepositoryMock.getData()["updateTask"]["task_status"])

    def testNokAsConflictByExistingUserNameMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_CONFLICT_BY_EXISTING_USER_NAME_MUTATION)

        self.assertEquals(ExceptionEnum.CODE_CONFLICT, graphqlResponseRepositoryMock.getFirstErrorCode())

    def testNokAsBadRequestByInvalidUserNameAndShortPasswordMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_USER_NAME_AND_SHORT_PASSWORD_MUTATION)

        self.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode())
        errorDetails = " ".join(graphqlResponseRepositoryMock.getFirstErrorDetails())
        self.assertStringContainsString("user_name:", errorDetails)
        self.assertStringContainsString("password:", errorDetails)

    def testNokAsBadRequestByEmptyUserNameUpdateUserMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_EMPTY_USER_NAME_UPDATE_USER_MUTATION)

        self.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode())

    def testNokAsConflictByEmailOfOtherUserUpdateUserMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_CONFLICT_BY_EMAIL_OF_OTHER_USER_UPDATE_USER_MUTATION)

        self.assertEquals(ExceptionEnum.CODE_CONFLICT, graphqlResponseRepositoryMock.getFirstErrorCode())

    def testNokAsNotFoundByOtherUserUuidUpdateUserMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_UPDATE_USER_MUTATION)

        self.assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode())
        self.assertNull(graphqlResponseRepositoryMock.getData().get("updateUser"))

    def testNokAsNotFoundByOtherUserUuidRegenerateApiTokenMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID_REGENERATE_API_TOKEN_MUTATION)

        self.assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode())

    def testNokAsNotFoundByOtherUserUuid(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID)

        self.assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode())
        self.assertNull(graphqlResponseRepositoryMock.getData().get("user"))

    def testNokAsNotFoundByFakeTaskUuid(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_NOT_FOUND_BY_FAKE_TASK_UUID)

        self.assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode())
        self.assertNull(graphqlResponseRepositoryMock.getData().get("task"))

    def testNokAsBadRequestByInvalidTargetDateMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_TARGET_DATE_MUTATION)

        self.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode())

    def testNokAsBadRequestByInvalidTaskTypeUpdateTaskMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_INVALID_TASK_TYPE_UPDATE_TASK_MUTATION)

        self.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, graphqlResponseRepositoryMock.getFirstErrorCode())

    def testNokAsNotFoundByTaskOfOtherUserUpdateTaskMutation(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_NOT_FOUND_BY_TASK_OF_OTHER_USER_UPDATE_TASK_MUTATION)

        self.assertEquals(ExceptionEnum.CODE_NOT_FOUND, graphqlResponseRepositoryMock.getFirstErrorCode())
        self.assertNull(graphqlResponseRepositoryMock.getData().get("updateTask"))

    def testNokAsUnauthorizedByInvalidToken(self) -> None:
        graphqlResponseRepositoryMock = self._getGraphQlResponseByTestCase(GraphQlTestCaseEnum.NOK_AS_UNAUTHORIZED_BY_INVALID_TOKEN)

        self.assertEquals(ExceptionEnum.CODE_UNAUTHORIZED, graphqlResponseRepositoryMock.getFirstErrorCode())

    def testNokAsBadRequestByEmptyQuery(self) -> None:
        GraphQlRequestMother.getInstance().getRequestByTestCase(GraphQlTestCaseEnum.NOK_AS_BAD_REQUEST_BY_EMPTY_QUERY)

        httpResponse = GraphQlControllerMother.getControllerInstance().invoke()

        self.assertInstanceOf(HttpResponder, httpResponse)
        self.assertEquals(ExceptionEnum.CODE_BAD_REQUEST, httpResponse.getStatusCode())

    def _getGraphQlResponseByTestCase(self, graphqlTestCaseEnum: str) -> GraphQlResponseRepositoryMock:
        """
        Toda peticion GraphQL que se ejecuta responde 200; lo que cambia es data / errors.
        """
        GraphQlRequestMother.getInstance().getRequestByTestCase(graphqlTestCaseEnum)

        httpResponse = GraphQlControllerMother.getControllerInstance().invoke()

        self.assertInstanceOf(HttpResponder, httpResponse)
        self.assertEquals(ExceptionEnum.CODE_OK, httpResponse.getStatusCode())
        return GraphQlResponseRepositoryMock.fromHttpResponse(httpResponse)
