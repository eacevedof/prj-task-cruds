from Modules.Shared.Application.Primitives import Primitives, getPrimitives


class UpdateUserResultDto:

    def __init__(self, primitives: Primitives):
        # el usuario modificado (UserEntity.toPrimitives)
        self._user = getPrimitives(primitives.get("user"))

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "UpdateUserResultDto":
        return UpdateUserResultDto(primitives)

    def getUser(self) -> Primitives:
        return self._user

    def toArray(self) -> Primitives:
        return self._user
