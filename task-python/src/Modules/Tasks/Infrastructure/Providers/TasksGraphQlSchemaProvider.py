from pathlib import Path

from Modules.Shared.Application.Primitives import getTrimmedString

from Modules.GraphQl.Domain.Enums.GraphQlEnum import GraphQlEnum
from Modules.GraphQl.Infrastructure.Providers.InterfaceGraphQlSchemaProvider import InterfaceGraphQlSchemaProvider

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Infrastructure.Providers.UsersGraphQlSchemaProvider import UsersGraphQlSchemaProvider

from Modules.Tasks.Infrastructure.Controllers.CreateTaskGraphQlController import CreateTaskGraphQlController
from Modules.Tasks.Infrastructure.Controllers.DeleteTaskGraphQlController import DeleteTaskGraphQlController
from Modules.Tasks.Infrastructure.Controllers.GetTaskGraphQlController import GetTaskGraphQlController
from Modules.Tasks.Infrastructure.Controllers.SearchTasksByUserGraphQlController import SearchTasksByUserGraphQlController
from Modules.Tasks.Infrastructure.Controllers.SearchTasksGraphQlController import SearchTasksGraphQlController
from Modules.Tasks.Infrastructure.Controllers.UpdateTaskGraphQlController import UpdateTaskGraphQlController


class TasksGraphQlSchemaProvider(InterfaceGraphQlSchemaProvider):
    """
    Parte de tasks en /graphql: tasks.graphql (extiende Query, Mutation y User de users) y sus resolvers, incluido
    User.tasks, que cuelga de User pero es de tasks. Va despues de UsersGraphQlSchemaProvider en el registro.
    """

    @staticmethod
    def getInstance() -> "TasksGraphQlSchemaProvider":
        return TasksGraphQlSchemaProvider()

    def getGraphQlFileContent(self) -> str:
        return (Path(__file__).parent / "tasks.graphql").read_text(encoding="utf-8")

    def getControllerResolvers(self) -> dict:
        return {
            GraphQlEnum.TYPE_QUERY: self._getQueryControllers(),

            GraphQlEnum.TYPE_MUTATION: self._getMutationControllers(),

            # Las tareas de un usuario.
            # User.tasks: solo ejemplo de extender un tipo de otro modulo, no lo consume ningun front (ver tasks.graphql)
            UsersGraphQlSchemaProvider.TYPE_USER: {
                "tasks": lambda gqlPayload, user: SearchTasksByUserGraphQlController.getInstance().invoke(
                    getTrimmedString(user.get(UsersKeyEnum.USER_UUID)),
                    gqlPayload
                ),
            },
        }

    def _getQueryControllers(self) -> dict:
        return {
            "tasks": lambda gqlPayload, parent: SearchTasksGraphQlController.getInstance().invoke(gqlPayload),
            "task": lambda gqlPayload, parent: GetTaskGraphQlController.getInstance().invoke(gqlPayload),
        }

    def _getMutationControllers(self) -> dict:
        return {
            "createTask": lambda gqlPayload, parent: CreateTaskGraphQlController.getInstance().invoke(gqlPayload),
            "updateTask": lambda gqlPayload, parent: UpdateTaskGraphQlController.getInstance().invoke(gqlPayload),
            "deleteTask": lambda gqlPayload, parent: DeleteTaskGraphQlController.getInstance().invoke(gqlPayload),
        }
