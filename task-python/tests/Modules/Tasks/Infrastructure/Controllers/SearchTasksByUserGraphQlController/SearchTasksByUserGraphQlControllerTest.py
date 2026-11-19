from Modules.GraphQl.Domain.Exceptions.GraphQlException import GraphQlException
from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum

from tests.AbstractTestCase import AbstractTestCase
from tests.bootstrap import runWithTestDb
from tests.Modules.Shared.Infrastructure.Components.HttpRequestMock import HttpRequestMock
from tests.Modules.Shared.Infrastructure.Repositories.TasksRawRepositoryMock import TasksRawRepositoryMock
from tests.Modules.Shared.Infrastructure.Repositories.UsersRawRepositoryMock import UsersRawRepositoryMock
from tests.Modules.Tasks.Infrastructure.Controllers.SearchTasksByUserGraphQlController.Mothers.SearchTasksByUserGraphQlControllerMother import SearchTasksByUserGraphQlControllerMother


class SearchTasksByUserGraphQlControllerTest(AbstractTestCase):
    """
    Resolver de User.tasks probado directamente: por /graphql solo se llega con el User del propio token, asi que el
    404 de un usuario ajeno (lo que cubria la query de tasks por user_uuid, retirada en 3.0.0) solo se puede probar aqui.
    """

    @classmethod
    def setUpClass(cls) -> None:
        runWithTestDb("search-tasks-by-user")

    def testOkAsSuccessByOwnUserUuid(self) -> None:
        self._setAuthenticatedRequest()

        taskPage = SearchTasksByUserGraphQlControllerMother.getControllerInstance().invoke(
            UsersRawRepositoryMock.USER_UUID_OK,
            {}
        )

        self.assertContains(
            TasksRawRepositoryMock.TASK_UUID_OK,
            [task["task_uuid"] for task in taskPage["items"]]
        )

    def testNokAsNotFoundByOtherUserUuid(self) -> None:
        self._setAuthenticatedRequest()

        try:
            SearchTasksByUserGraphQlControllerMother.getControllerInstance().invoke(
                UsersRawRepositoryMock.USER_UUID_TO_UPDATE,
                {}
            )
            self.fail("se esperaba GraphQlException")
        except GraphQlException as e:
            # el catch (GraphQlException) de PHP: lo demas (el fallo de fail() incluido) sigue su camino
            self.assertEquals(ExceptionEnum.CODE_NOT_FOUND, e.getCode())

    def _setAuthenticatedRequest(self) -> None:
        HttpRequestMock.getInstance() \
            .setBearerToken(UsersRawRepositoryMock.USER_API_TOKEN_OK) \
            .getMockedInstance()
