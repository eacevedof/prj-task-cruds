from Modules.Shared.Application.AbstractInputDto import AbstractInputDto
from Modules.Shared.Application.Primitives import Primitives, getInt, getTrimmedString


class ListUsersDto(AbstractInputDto):

    def __init__(self, primitives: Primitives):
        super().__init__(primitives)

        self._authUserId = getInt(primitives.get("authUserId"))
        self._authUserUuid = getTrimmedString(primitives.get("authUserUuid"))

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "ListUsersDto":
        return ListUsersDto(primitives)

    def getAuthUserId(self) -> int:
        return self._authUserId

    def getAuthUserUuid(self) -> str:
        return self._authUserUuid
