from Modules.Shared.Application.Primitives import Primitives, getInt, getTrimmedString

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum


class DeleteUserResultDto:

    def __init__(self, primitives: Primitives):
        self._userUuid = getTrimmedString(primitives.get("userUuid"))
        self._affected = getInt(primitives.get("affected"))

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "DeleteUserResultDto":
        return DeleteUserResultDto(primitives)

    def getUserUuid(self) -> str:
        return self._userUuid

    def getAffected(self) -> int:
        return self._affected

    def toArray(self) -> Primitives:
        return {
            UsersKeyEnum.USER_UUID: self._userUuid,
            UsersKeyEnum.AFFECTED: self._affected,
        }
