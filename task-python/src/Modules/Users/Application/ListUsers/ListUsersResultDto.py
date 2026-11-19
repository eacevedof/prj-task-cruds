from Modules.Shared.Application.Primitives import Primitives


class ListUsersResultDto:

    def __init__(self, primitives: Primitives):
        # lista de UserEntity.toPrimitives
        users = primitives.get("users")
        self._users: list[Primitives] = users if isinstance(users, list) else []

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "ListUsersResultDto":
        return ListUsersResultDto(primitives)

    def getUsers(self) -> list[Primitives]:
        return self._users

    def toArray(self) -> list[Primitives]:
        return self._users
