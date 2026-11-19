from Modules.Shared.Application.Primitives import Primitives, getPrimitives


class GetUserResultDto:

    def __init__(self, primitives: Primitives):
        # el usuario (UserEntity.toPrimitives)
        self._user = getPrimitives(primitives.get("user"))

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "GetUserResultDto":
        return GetUserResultDto(primitives)

    def getUser(self) -> Primitives:
        return self._user

    def toArray(self) -> Primitives:
        return self._user
