from Modules.Shared.Application.AbstractInputDto import AbstractInputDto
from Modules.Shared.Application.Primitives import Primitives, getInt, getTrimmedString


class UpdateUserDto(AbstractInputDto):

    def __init__(self, primitives: Primitives):
        super().__init__(primitives)

        self._authUserId = getInt(primitives.get("authUserId"))
        self._authUserUuid = getTrimmedString(primitives.get("authUserUuid"))
        self._userUuid = getTrimmedString(primitives.get("userUuid"))
        self._userName = getTrimmedString(primitives.get("userName"))
        self._userEmail = getTrimmedString(primitives.get("userEmail"))

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "UpdateUserDto":
        return UpdateUserDto(primitives)

    def getAuthUserId(self) -> int:
        return self._authUserId

    def getAuthUserUuid(self) -> str:
        return self._authUserUuid

    def getUserUuid(self) -> str:
        return self._userUuid

    def getUserName(self) -> str:
        return self._userName

    def getUserEmail(self) -> str:
        return self._userEmail
