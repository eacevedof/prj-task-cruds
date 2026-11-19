from Modules.Shared.Application.Primitives import Primitives, getInt, getTrimmedString


class AuthenticateUserResultDto:
    """
    userId es el que usan todas las queries (mas ligero que el uuid); userUuid solo para comparar con el
    user_uuid que llega de fuera (argumento GraphQL, opcion de consola).
    """

    def __init__(self, userId: int, userUuid: str):
        self._userId = userId
        self._userUuid = userUuid

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "AuthenticateUserResultDto":
        return AuthenticateUserResultDto(
            getInt(primitives.get("userId")),
            getTrimmedString(primitives.get("userUuid")),
        )

    def getUserId(self) -> int:
        return self._userId

    def getUserUuid(self) -> str:
        return self._userUuid

    def toAuthPrimitives(self) -> Primitives:
        return {
            "authUserId": self._userId,
            "authUserUuid": self._userUuid,
        }
