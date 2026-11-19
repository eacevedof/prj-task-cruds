from Modules.Shared.Application.AbstractInputDto import AbstractInputDto
from Modules.Shared.Application.Primitives import Primitives, getTrimmedString


class AuthenticateConsoleUserDto(AbstractInputDto):

    def __init__(self, primitives: Primitives):
        super().__init__(primitives)

        self._userUuid = getTrimmedString(primitives.get("userUuid"))

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "AuthenticateConsoleUserDto":
        return AuthenticateConsoleUserDto(primitives)

    def getUserUuid(self) -> str:
        return self._userUuid
