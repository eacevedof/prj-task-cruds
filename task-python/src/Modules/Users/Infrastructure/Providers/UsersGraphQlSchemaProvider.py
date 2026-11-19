import os

from Modules.GraphQl.Domain.Enums.GraphQlEnum import GraphQlEnum
from Modules.GraphQl.Infrastructure.Providers.InterfaceGraphQlSchemaProvider import (
    GraphQlResolversByType,
    InterfaceGraphQlSchemaProvider,
)

from Modules.Users.Infrastructure.Controllers.CreateUserGraphQlController import CreateUserGraphQlController
from Modules.Users.Infrastructure.Controllers.DeleteUserGraphQlController import DeleteUserGraphQlController
from Modules.Users.Infrastructure.Controllers.GetUserGraphQlController import GetUserGraphQlController
from Modules.Users.Infrastructure.Controllers.ListUsersGraphQlController import ListUsersGraphQlController
from Modules.Users.Infrastructure.Controllers.LogInUserGraphQlController import LogInUserGraphQlController
from Modules.Users.Infrastructure.Controllers.RegenerateUserApiTokenGraphQlController import RegenerateUserApiTokenGraphQlController
from Modules.Users.Infrastructure.Controllers.UpdateUserGraphQlController import UpdateUserGraphQlController


class UsersGraphQlSchemaProvider(InterfaceGraphQlSchemaProvider):
    """
    Parte de users en /graphql: users.graphql (define Query, Mutation y User) y sus resolvers. El resolver se crea
    solo si la consulta pide su campo.
    """

    # tipo que define users.graphql y extienden otros modulos (Tasks le anade tasks)
    TYPE_USER = "User"

    @staticmethod
    def getInstance() -> "UsersGraphQlSchemaProvider":
        return UsersGraphQlSchemaProvider()

    def getGraphQlFileContent(self) -> str:
        with open(os.path.join(os.path.dirname(__file__), "users.graphql"), "r", encoding="utf-8") as graphQlFile:
            return graphQlFile.read()

    def getControllerResolvers(self) -> GraphQlResolversByType:
        return {
            GraphQlEnum.TYPE_QUERY: self._getQueryControllers(),
            GraphQlEnum.TYPE_MUTATION: self._getMutationControllers(),
        }

    def _getQueryControllers(self) -> dict:
        return {
            "users": lambda gqlPayload=None: ListUsersGraphQlController.getInstance().invoke(),
            "user": lambda gqlPayload=None: GetUserGraphQlController.getInstance().invoke(gqlPayload),
        }

    def _getMutationControllers(self) -> dict:
        return {
            "createUser": lambda gqlPayload=None: CreateUserGraphQlController.getInstance().invoke(gqlPayload),
            "logIn": lambda gqlPayload=None: LogInUserGraphQlController.getInstance().invoke(gqlPayload),
            "updateUser": lambda gqlPayload=None: UpdateUserGraphQlController.getInstance().invoke(gqlPayload),
            "deleteUser": lambda gqlPayload=None: DeleteUserGraphQlController.getInstance().invoke(gqlPayload),
            "regenerateApiToken": lambda gqlPayload=None: RegenerateUserApiTokenGraphQlController.getInstance().invoke(gqlPayload),
        }
