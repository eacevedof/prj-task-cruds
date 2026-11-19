from Modules.Shared.Application.AbstractInputDto import AbstractInputDto
from Modules.Shared.Application.Primitives import Primitives, getTrimmedString


class CreateUserDto(AbstractInputDto):

    def __init__(self, primitives: Primitives):
        super().__init__(primitives)

        self._userName = getTrimmedString(primitives.get("userName"))
        self._userEmail = getTrimmedString(primitives.get("userEmail"))
        self._password = getTrimmedString(primitives.get("password"))

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "CreateUserDto":
        return CreateUserDto(primitives)

    def getUserName(self) -> str:
        return self._userName

    def getUserEmail(self) -> str:
        return self._userEmail

    def getPassword(self) -> str:
        return self._password
